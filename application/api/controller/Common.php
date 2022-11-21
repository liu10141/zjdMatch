<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date:19/2/11
 * Time: 下午4:37
 */

namespace app\api\controller;

use app\common\lib\Aes;
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
    public $token = '';
    public $headers = '';
    public $app_type = '';

    /**
     * 初始化的方法
     *所有的控制器访问之前首先进行sign校验
     */
    public function _initialize()
    {
        // 初始化的方法首先检测sign
//         $this->testAes();die;
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
        $this->headers = $headers;
//        $validate = new Validate(
//            [
//                'app-type' => 'require',
////                'sign' => 'require',
//            ], [
//            'app-type.require' => '设备类型不能为空',
////            'sign.require' => '签名不能为空',
//        ]);
//        if (!$validate->check($headers)) {
//            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
//            die;
//        }
//
//        $this->app_type = $headers['app-type'];
//
////        if (empty($headers['sign'])) {
////            echo $this->show(config('code.error'), 'sign不存在', ['code' => 202]);
////            die;
////        }
//        //判断App_type是否在允许的列表之内
//        if (!in_array($headers['app-type'], config('app.apptypes'))) {
//            echo $this->show(config('code.error'), '设备类型不支持请求', ['code' => 202]);
//            die;
//        }
        return;
//        //需要sign
//        if (!IAuth::checkSignPass($headers)) {
//            echo $this->show(config('code.error'), 'sign授权码过期', ['code' => 202]);
//            die;
//        }
//        //存储的时间为null时时永久
//         $RedisObj = new RedisTools();
//         $RedisObj->set($headers['sign'], 1, config('app.app_sign_cache_time'));
        //可缓存的类型 1、文件  2、mysql 3、redis
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

}