<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date:19/2/11
 * Time: 下午4:37
 */

namespace app\api\controller\v1;

use app\common\controller\RedisTools;
use app\common\lib\Aes;
use app\common\lib\IAuth;
use think\Cache;
use think\Controller;

/**
 * API模块 公共的控制器
 * Class Common
 * @package app\api\controller
 */
class Common extends Controller
{
    /**
     * headers信息
     * @var string
     */
    public $headers = '';
    public $page = 1;
    public $size = 10;
    public $from = 0;
    public $id = '';
    public $token = '';
    public $app_type = '';

    /**
     * 初始化的方法
     *所有的控制器访问之前首先进行sign校验
     */
    public function _initialize()
    {
        // 初始化的方法首先检测sign
        $this->checkRequestAuth();
    }

    /*
      *sign 加密需要  客户端工程师 ，
      *解密：服务端工程师
      *headers body 仿照sign 做参数的加解密
      *基础参数校验
      *判断header头是否含有SIGN 是前段加密过得字符串
      */
    public function checkRequestAuth()
    {
        $headers = request()->header();
        $is_need_app_type = config('app.is_need_app_type');
        $is_need_sign = config('app.is_need_sign');
//        是否需要校验签名
        if ($is_need_sign) {
            if (empty($headers['sign'])) {
                echo $this->show(config('code.error'), 'sign不存在', ['code' => 202]);
                die;
            }
            //需要sign
            if (!IAuth::checkSignPass($headers)) {
                echo $this->show(config('code.error'), '签名验证失败', ['code' => 202]);
                die;
            }
            //存储的时间为null时时永久
            $RedisObj = new RedisTools();
            $RedisObj->set($headers['sign'], 1, config('app.app_sign_cache_time'));

        }
//        是否需要传输设备类型
        if ($is_need_app_type) {
            //判断App_type是否在允许的列表之内
            if (!in_array($headers['app-type'], config('app.apptypes'))) {
                echo $this->show(config('code.error'), '设备类型不支持请求', ['code' => 202]);
                die;
            }
            $this->app_type = $headers['app-type'];
        }
        //可缓存的类型 1、文件  2、mysql 3、redis
        $this->headers = $headers;
    }


    /**
     * @param $status
     * @param $message
     * @param array $data
     * 到时候所有的调用$this->show();
     */
    public function show($status, $message, $data)
    {
        return show($status, $message, $data);
        $obj = new Aes();
        return $obj->encrypt(show($status, $message, $data));
    }

    /**
     * 测试加密生成sign
     */
    public function testAes()
    {
        $data = [
            'did' => '12345dg',
            'time' => time(),
        ];
        echo IAuth::setSign($data);
        exit;
//      解密
        echo (new Aes())->decrypt($str);
        exit;
    }

    /**
     * @return mixed
     * 获取token
     * 中控服务器
     * 高度封装框架
     * 所有的请求都来这里那请求token
     */
    public function getAccessToken()
    {
        $accessTokenKey = config('wxconfig.gh_accessTokenKey');
        //这里获取accesstoken  请根据自己的程序进行修改
        $wx_access_token = Cache::get($accessTokenKey);
        if (!$wx_access_token) {
            $appid = config('wxconfig.gzh_appid');//
            $secret = config('wxconfig.gzh_secret');//
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $wx_access_token = $this->get_url($url, "json")["access_token"];
            Cache::set($accessTokenKey, $wx_access_token, 7000);
        }
        return $wx_access_token;

    }

    /**
     * @return mixed
     * 获取分享的js权限
     */
    public function getJsApiTicket()
    {
        $jsapi_ticket_Key = config('wxconfig.jsapi_ticket_key');
        $jsapi_ticket = Cache::get($jsapi_ticket_Key);
        if (empty($jsapi_ticket)) {
            $accessTokenKey = config('wxconfig.accessTokenKey');
            $accessToken = Cache::get($accessTokenKey);
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode(curlGet($url), true);
            if ($res) {
                $ticket = isset($res['ticket']) ? $res['ticket'] : '';
                if (empty($ticket)) {
                    return false;
                }
                Cache::set($jsapi_ticket_Key, $ticket, 7000);
            }
        } else {
            $ticket = $jsapi_ticket;
        }
        return $ticket;
    }

    /**
     * @param $url
     * @param $v
     * @return bool|mixed|string
     * 请求数据
     */
    public function get_url($url, $v)
    {
        //http api接口json数据
        $html = file_get_contents($url);
        if ($v == "json") {
            return json_decode($html, true);
        } else {
            return $html;
        }
    }
}
