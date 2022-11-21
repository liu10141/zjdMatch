<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 17/7/28
 * Time: 上午12:29
 */

return [
    'password_pre_halt' => '124miaohu12ahjsmdsa',// 密码加密盐
    'aeskey' => 'Pbye9tRH5qACd3DM',//aes 密钥 , 服务端和客户端必须保持一致
    'apptypes' => [
        'ios',
        'android',
    ],
    'is_need_sign' => false,//是否需要签名
    'is_need_app_type' => false,//是否需要设备类型
    'app_sign_time' => 10,// sign失效时间
    'app_sign_cache_time' => 15,// sign 缓存失效时间
    'login_time_out_day' => 30,// 登录token的失效时间
];