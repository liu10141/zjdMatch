<?php


namespace app\api\controller\v1;


use app\api\controller\Common;
use app\common\controller\SendAlisms;
use think\Cache;
use think\Controller;
use think\Validate;

class Sendsms extends Controller
{
    /**
     * 发送短信
     * 接收参数 校验参数 处理参数 存储修改参数
     *
     */
    public function sendSms()
    {
        $params = input('post.');
        $validate = new Validate([
            'mobile' => 'require|max:11|/^1[3-9]{1}[0-9]{9}$/',
        ], [
            'mobile.require' => '手机号格式不正确',
            'mobile./^1[3-8]{1}[0-9]{9}$/' => '手机号格式不正确',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError());
            die;
        }
        $object = new SendAlisms();
        $phone = $params['mobile'];
        $code = mt_rand(1001, 9999);
        Cache::set($phone . 'cbcode', $code, 5 * 60);
        $res = $object->send_verify($phone, $code);
        echo show(config('code.success'), '发送成功', $res);
        die;
    }
}