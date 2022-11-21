<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 17/7/28
 * Time: 上午12:27
 */

namespace app\common\lib;

use think\Cache;
use think\Validate;

/**
 * Iauth相关
 * Class IAuth
 */
class IAuth
{

    /**
     * 设置密码
     * @param string $data
     * @return string
     */
    public static function setPassword($data)
    {
//        密码加盐
        return md5($data . config('app.password_pre_halt'));
    }

    /**
     * 生成每次请求的sign
     * @param array $data
     * @return string
     * 客户端再来请求之前先生成sign
     * 方法自己测试使用客户端
     */
    public static function setSign($data = [])
    {
        // 1 按字段建排序
        ksort($data);
        // 2拼接字符串数据  &
        $string = http_build_query($data);
        // 3通过aes来加密
        $string = (new Aes())->encrypt($string);
        return $string;
    }

    /**
     * 检查sign是否正常
     * @param array $data
     * @param $data 就是请求头
     * @return boolen
     * 解密sign是否正确
     */
    public static function checkSignPass($data)
    {
        $validate = new Validate([
            'did' => 'require',
            'timestamp' => 'require'
        ], [
            'did.require' => '设备识别码不能为空',
            'timestamp.require' => '时间戳不能为空',
        ]);
        if (!$validate->check($data)) {
            return false;
        }
//        解密客户端加密的sign进行解密
        $str = (new Aes())->decrypt($data['sign']);
        if (empty($str)) {
            return false;
        }
        /**
         *did=xx&app-type=3
         *第一个参数是$str 第二个要赋变量
         *只要有一个条件不符合就return  false
         */
        parse_str($str, $arr);
        if (!is_array($arr) || empty($arr['did']) || $arr['did'] != $data['did']) //        解密之后判断did是否正确
        {
            return false;
        }
        /**
         * 时间戳的加入安全性有助于提高
         * 客户端获取时间与服务器校验时间
         * 13位的时间戳到时候可以还原为10位的时间戳
         * 当前时间减去请求的时间 超时说明sign 验证失败
         */

        if (!config('app_debug')) {
            // 时间判断是否
            if ((time() - $arr['timestamp']) > config('app.app_sign_time')) {
                return false;
            }
            //唯一性判定cache中查到sign 说明
            if (Cache::get($data['sign'])) {
                return false;
            }
            return true;
        }

    }

    /**
     * 设置登录的token  - 唯一性的登录标识
     * @param string $phone
     * @return string
     */
    public static function setAppLoginToken($phone = '')
    {
        $str = md5(uniqid(md5(microtime(true)), true));
        $str = sha1($str . $phone);
        return $str;
    }

}