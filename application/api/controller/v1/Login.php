<?php


namespace app\api\controller\v1;


use app\api\controller\Common;
use app\common\controller\RedisTools;
use app\common\lib\Aes;
use app\common\lib\IAuth;
use think\Cache;
use think\Db;
use think\Validate;
use WXBizData\WxBizDataCrypt;


class Login extends Common
{
    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 微信小程序 登录
     * 登录之前判断 uniadd
     * 这次即送90天会员
     */
    public function wx_mini_login()
    {
        $params = input('post.');
        $validate = new Validate(
            [
                'encryptedData' => 'require',
                'code' => 'require',
                'iv' => 'require',
                'rawData' => 'require',
                'signature' => 'require',
            ], [
            'encryptedData.require' => '加密数据',
            'code.require' => '微信code',
            'iv.require' => '偏移量',
            'rawData.require' => '原数据',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        //组装数据
        $code = $params['code'];
        $rawData = $params['rawData'];
        $signature = $params['signature'];
        $encryptedData = $params['encryptedData'];
        $iv = $params['iv'];
        /**
         * 4.server调用微信提供的jsoncode2session接口获取openid, session_key,
         * 调用失败应给予客户端反馈, 微信侧返回错误则可判断为恶意请求, 可以不返回. 微信文档链接
         * 这是一个 HTTP 接口，开发者服务器使用登录凭证 code 获取 session_key 和 openid。其中 session_key 是对用户数据进行加密签名的密钥。
         * 为了自身应用安全，session_key 不应该在网络上传输。
         * 接口地址："https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code"
         */
        $queryParams = [
            'appid' => config('wxconfig.miniAppId'),
            'secret' => config('wxconfig.miniSecret'),
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?';
        $url = $url . http_build_query($queryParams);
        $reqData = $this->_request($url); // TODO 生产环境切换
        $reqData = json_decode($reqData, true); //TODO 生产环境切换
        $sessionKey = isset($reqData['session_key']) ? $reqData['session_key'] : '';
        if (empty($sessionKey)) {
            echo show(config('code.error'), '解密失败', ['code' => 200]);
            die();
        }
        //缓存session_key 不定期过期 过期重新登录
        $redisObj = new RedisTools();
        $redisObj->set($reqData['openid'] . 'session_key', $sessionKey);
        //判断
        if (isset($reqData['openid']) && !empty($reqData['openid'])) {
            $wxinfo = $this->encrypt_info($encryptedData, $iv, $sessionKey);
            $checksign = sha1($rawData . $sessionKey);
            if ($checksign !== $signature) {
                echo show(config('code.error'), '签名错误', ['code' => 200]);
                die();
            }

//            Db::startTrans();
            $userinfo = Db::name('user')
                ->where('openid', $reqData['openid'])
                ->field('id,status')
                ->find();
            $unionid = $userinfo['id'];
            $token = IAuth::setAppLoginToken($reqData['openid']);
            $obj = new Aes();
            if (!empty($unionid)) {
                if ($userinfo['status'] == 2) {
                    echo show(config('code.error'), '账号已冻结,如有疑问请联系客服处理', []);
                    die;
                }
                $upuserinfo =
                    [
                        'nickname' => $wxinfo['nickName'],
                        'headimgurl' => str_replace('/132', '/0', $wxinfo['avatarUrl']),
                        'update_time' => date('Y-m-d H:i:s'),
                    ];
                $token_time_out = strtotime("+" . config('code.login_time_out_day') . " days");
                $upuserData =
                    [
                        'token' => $token,
                        'token_time_out' => $token_time_out,
                        'update_time' => date('Y-m-d H:i:s'),
                    ];
                $userId = $unionid;
                Db::name('user')
                    ->where('id', $userId)
                    ->update($upuserData);
                Db::name('userinfo')
                    ->where('user_id', $userId)
                    ->update($upuserinfo);
                $loginToken = $obj->encrypt($token . '||' . $userId);
                $result = [
                    'token' => $loginToken,
                ];
                echo show(config('code.success'), '登录成功', $result);
                die;
            } else {
                $data =
                    [
                        'token' => $token,
                        'openid' => $reqData['openid'],
                        'token_time_out' => strtotime("+" . config('code.login_time_out_day') . " days"),
                        'vip_time_out' => strtotime("+" . config('code.send_vip_day') . " days"),
                        'create_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s'),
                    ];
                //用户信息
                $userId = Db::name('user')->insertGetId($data);
                $userInfoData = [
                    'nickname' => $wxinfo['nickName'],
                    'headimgurl' => str_replace('/132', '/0', $wxinfo['avatarUrl']),
                    'user_id' => $userId,
                    'vip_no' => $this->getVipNo(),
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ];
                Db::name('userinfo')->insert($userInfoData);
            }
            $loginToken = $obj->encrypt($token . '||' . $userId);
            $result = [
                'token' => $loginToken,
            ];

//            Db::commit();
            echo show(config('code.success'), '登录成功', $result);
            die;
        }
        echo show(config('code.error'), '登录失败', ['code' => 202]);
        die;
    }

    /**
     * 获取会员订单编号
     */
    public function getVipNo()
    {
        $cachenum = Cache::get('VipNo');
        if (!$cachenum) {
            $cachenum = 1;
            Cache::set('VipNo', $cachenum);
            $VipNo = sprintf("%04d", $cachenum);//生成4位数，不足前面补0 sprintf("%03d", $cachenum);//生成4位数，不足前面补0
        } else {
            do {
                $cachenum = $cachenum + 1;
            } while (stripos($cachenum, '4') !== false);
            $VipNo = sprintf("%04d", $cachenum);//生成4位数，不足前面补0
            Cache::set('VipNo', $cachenum);
        }
        return $VipNo;
    }

    /**
     * 解密数据保存起来
     * 使用第4步返回的session_key解密encryptData,
     * 将解得的信息与rawData中信息进行比较, 需要完全匹配,
     * 解得的信息中也包括openid, 也需要与第4步返回的openid匹配.
     * 解密失败或不匹配应该返回客户相应错误.
     * （使用官方提供的方法即可）
     */
    public function encrypt_info($encryptedData, $iv, $sessionKey)
    {
        $wxencryptObj = new WxBizDataCrypt(config('wxconfig.miniAppId'), $sessionKey);
        $errCode = $wxencryptObj->decryptData($encryptedData, $iv, $data);
        if ($errCode !== 0) {
            echo show(config('code.error'), '数据解密失败', []);
            die;
        }
        return json_decode($data, true);
    }

    /**
     * @param $access_token
     * @param $openid
     * 获取用户信息
     */
    public function get_userInfo($openinfo)
    {
        if (!is_array($openinfo)) {
            echo show(config('code.error'), '授权信息失败', ['code' => 200]);
            die;
        }
        $access_token = $openinfo['access_token'];
        $openid = $openinfo['openid'];
        //查询是否过期过期刷新
        $baseUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $content = $this->_request($baseUrl);
        $result = json_decode($content, true);
        $updata =
            [
                'nickname' => $result['nickname'],
                'headimgurl' => $result['headimgurl'],
            ];
        Db::name('user')->where('openid', $result['openid'])->update($updata);
        $token = IAuth::setAppLoginToken($openid);
        cache::set($token, 1, strtotime("+25 days"));
        $updata['token'] = $token;
        echo show(config('code.success'), '授权信息', $updata);
        die;
    }

    /**
     * @param $curl
     * @param bool $https
     * @param string $method
     * @param null $data
     * @return bool|string
     * 发起请求
     */
    public function _request($curl, $https = true, $method = 'GET', $data = null)
    {
        // 创建一个新cURL资源
        $ch = curl_init();
        // 设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $curl);    //要访问的网站
        curl_setopt($ch, CURLOPT_HEADER, false);    //启用时会将头文件的信息作为数据流输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //将curl_exec()获取的信息以字符串返回，而不是直接输出。

        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //FALSE 禁止 cURL 验证对等证书（peer's certificate）。
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  //验证主机
        }
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);  //发送 POST 请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  //全部数据使用HTTP协议中的 "POST" 操作来发送。
        }
        // 抓取URL并把它传递给浏览器
        $content = curl_exec($ch);
        if ($content === false) {
            return "网络请求出错: " . curl_error($ch);
            exit();
        }
        //关闭cURL资源，并且释放系统资源
        curl_close($ch);
        return $content;
    }

}