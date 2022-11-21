<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date:19/2/11
 * Time: 下午4:37
 */

namespace app\admin\controller;

use app\common\controller\RedisTools;
use think\Controller;
use app\common\lib\Aes;
use app\common\lib\IAuth;
use think\Validate;

/**
 * API模块 公共的控制器
 * Class Common 0
 * @package app\api\controller
 */
class Common extends Controller
{
    /**
     * @param $status
     * @param $message
     * @param array $data
     * 到时候所有的调用$this->show();
     */
    public function show($status, $message, $data = [])
    {
        if (config('app_debug')) {
            $info = show($status, $message, $data);
        } else {
            $obj = new Aes();
            $info = $obj->encrypt(show($status, $message, $data));
        }
        return $info;
    }

}