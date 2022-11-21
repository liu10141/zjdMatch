<?php


namespace app\api\controller\v1;


use think\Db;
use think\Validate;

class WxLogin extends Common
{
    const GET_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token'; // 获取access_token url
    const GET_USER_INFO_URL = 'https://api.weixin.qq.com/sns/userinfo';               // 获取用户信息url
    const GET_REFRESH_URL = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';   //刷新access_token
    const GET_CODE = 'https://open.weixin.qq.com/connect/qrconnect';  // 获取code(网页授权使用)

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 微信绑定微信推送
     */
    public function wxBuild()
    {
        $params = input('post.');
        $validate = new Validate([
            'code' => 'require',
            'mobile' => 'require|max:11|/^1[3-9]{1}[0-9]{9}$/',
        ], [
            'code.require' => '请填写code',
            'mobile.require' => '请填写手机号',
            'mobile./^1[3-9]{1}[0-9]{9}$/' => '手机号格式不正确',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $userinfo = Db::name('userinfo')
            ->where('mobile', $params['mobile'])
            ->field('user_id,gh_openid')
            ->find();
        if (empty($userinfo)) {
            echo $this->show(config('code.error'), '该手机号暂未关联小程序，请先前往小程序完善信息', []);
            die;
        }
        if (!empty($userinfo['gh_openid'])) {
            echo $this->show(config('code.error'), '该手机号已绑定', []);
            die;
        }
        $code = $params['code'];
        $token_info = $this->getToken($code);
        if (isset($token_info['errcode'])) {
            echo $this->show(config('code.error'), '绑定失败', []);
            die;
        }
        Db::name('userinfo')
            ->where('user_id', $userinfo['user_id'])
            ->update(['gh_openid' => $token_info['openid']]);
        echo $this->show(config('code.success'), '绑定成功', []);
        die;
    }

    /**
     * 获取二维码
     */
    public function getQrcode()
    {
        $appid = config('wxconfig.appid');
        $url = 'https://open.weixin.qq.com/connect/qrconnect?appid=' . $appid . '&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect';
    }

    /**
     * 获取用户关注状态
     * 出現登錄失敗問題
     * 重置密鑰
     */
    public function getUserSubscribe($openid)
    {
        $token = file_get_contents('http://api.greatorange.cn/mfy/api/getPppAccessToken');
        $AccessToken = json_decode($token, true);
        $Access = $AccessToken['data']['token'];
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$Access&openid=$openid&lang=zh_CN";
        $user_info = $this->httpsRequest($url);
        $subscribe = json_decode($user_info, true)['subscribe'];
        return $subscribe;
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

    /**
     * 获取token和openid
     * @param string $code 客户端传回的code
     * @return array 获取到的数据
     * 微信授权登录
     */
    public function getToken($code)
    {
        $get_token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, [
            'appid' => config('wxconfig.gzh_appid'),
            'secret' => config('wxconfig.gzh_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code'
        ]);
        $token_info = $this->httpsRequest($get_token_url);
        return json_decode($token_info, true);
    }

    /**
     * 获取服务器数据
     * @param string $url 请求的url
     * @return  unknown    请求返回的内容
     */
    public function httpsRequest($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     * 获取用户信息
     * @param string $openid 用户的标识
     * @param string $access_token 调用接口凭证
     * @return array 用户信息
     */
    public function getUserinfo($openid, $access_token)
    {
        $get_userinfo_url = $this->combineURL(self::GET_USER_INFO_URL, [
            'openid' => $openid,
            'access_token' => $access_token,
            'lang' => 'zh_CN'
        ]);
        $user_info = $this->httpsRequest($get_userinfo_url);
        return json_decode($user_info, true);
    }

    /**
     * 拼接url
     * @param string $baseURL 请求的url
     * @param array $keysArr 参数列表数组
     * @return string           返回拼接的url
     */
    public function combineURL($baseURL, $keysArr)
    {
        $combined = $baseURL . "?";
        $valueArr = array();
        foreach ($keysArr as $key => $val) {
            $valueArr[] = "$key=$val";
        }
        $keyStr = implode("&", $valueArr);
        $combined .= ($keyStr);
        return $combined;
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $params = input('post.');
        echo oldshow(config('code.success'), '退出登录', ['userinfo' => 200]);
        die;
    }
}