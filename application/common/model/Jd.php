<?php


namespace app\common\model;


use think\Cache;
use think\Model;

class Jd extends Model
{
    /***
     * @param $url
     * @param $data
     * @param bool $isToken
     * @return mixed|string
     * 获取设备列表
     */
    public function geteviceInfo($url, $data, $isToken = true)

    {
//        https://api.jiudacheng.com/proxy/deviceInfo/get/page
        $token = Cache::get(config('you_ren_yun.usr_token'));
        if (!$token) {
            $loginRes = $this->login();
            $token = $loginRes['data']['token'];
            $usrUserType = $loginRes['data']['usrUserType'];
            Cache::set(config('you_ren_yun.usr_token'), $token, 7000);
            Cache::set(config('you_ren_yun.usrUserType'), $usrUserType, 7000);
        }
        $post_data = array_merge($data, array('token' => $token));

        //请求有人云api
        $post_url = config('you_ren_yun.API_ADDRESS') . $url;
        $dataRes = http_json_data($post_url, $post_data, $token);
        $dataRes = json_decode($dataRes, true);
        return $dataRes;
    }

    /**
     * 获取九达请求token
     */
    public function getjdToken()
    {

        $url = 'https://api.jiudacheng.com/proxy/user/login';
        $mobile = config('jd.account');
        $passWord = config('jd.password');
        $data = [
            'mobile' => $mobile,
            'passWord' => $passWord,

        ];
    }
}