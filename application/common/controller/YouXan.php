<?php


namespace app\common\controller;


use think\Cache;
use think\Controller;
use Youzan\Open\Client;
use Youzan\Open\Token;

class YouXan extends Controller
{
    /**
     * @return mixed
     * 获取有赞请求token
     */
    public function getAccessToken()
    {
        $access_token = Cache::get('YouzanToken');
        if ($access_token == false) {
            $clientId = config('youzan.clientId');//有赞云颁发给开发者的应用ID
            $clientSecret = config('youzan.clientSecret');//有赞云颁发给开发者的应用secret
            $kdtid = config('youzan.grant_id');//店铺id
            $config['refresh'] = false;  //是否获取refresh_token(可通过refresh_token刷新token);
            $youzanObj = new Token($clientId, $clientSecret);
            $resp = $youzanObj->getSelfAppToken($kdtid, $config);
            $access_token = Cache::set('YouzanToken', $resp['access_token'], 86400 * 6);
            return $access_token;
        }
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     * 有赞发起请求
     */
    public function YouZanRequest($method, $apiVersion, $params)
    {
        $accessToken = $this->getAccessToken();
        $client = new Client($accessToken);
        //设置参数
        $response = $client->post($method, $apiVersion, $params);
        return $response;
    }

    /**
     * @param $mobile
     */
    public function getYouZanUserInfo($mobile)
    {
        $params = [
            'open_id_type' => '',
            'weixin_open_id' => '',
            'mobile' => $mobile,
            'yz_open_id' => '',
            'weixin_union_id' => '',
            'result_type_list' => '[0,1]',
        ];
        $method = 'youzan.users.info.query';
        $apiVersion = '1.0.0';
        $resData = $this->YouZanRequest($method, $apiVersion, $params);

    }

    /**
     * 同步有赞积分数据
     */
    public function getYouZanDqScore()
    {
        $method = 'youzan.crm.customer.points.get';
        $apiVersion = '1.0.0';

        //设置参数
        $params = [
            "is_do_extpoint" => "true",
            "user" => '["account_type"=>2,account_id=>17798562229]'
        ];
        $resData = $this->YouZanRequest($method, $apiVersion, $params);
    }

    /**
     * 给用户增加积分
     */
    public function getYouZanIncScore()
    {
        $method = 'youzan.crm.customer.points.increase';
        $apiVersion = '4.0.0';
        $mobile = '13148484985';
        $points = 10;
        //设置参数
        //设置参数
        $params = [
            "params" => "[
            \"reason\"=>\"比赛奖励积分\",
            \"user\"=>[
            \"account_type\"=>2,
            \"account_id\"=>$mobile
            ],
            \"points\"=>$points,
            ]"
        ];
        $resData = $this->YouZanRequest($method, $apiVersion, $params);
    }

    /**
     * 给用户减少积分
     */
    public function getYouZandecScore()
    {
        $method = 'youzan.crm.customer.points.decrease';
        $apiVersion = '4.0.0';

//设置参数
        $params = [
            "params" => "[\"reason\"=>\"删帖扣除积分\",\"check_customer\"=>true,\"user\"=>[\"account_type\"=>2,\"account_id\"=>\"13148484985\"],\"points\"=>1]"
        ];

        $resData = $this->YouZanRequest($method, $apiVersion, $params);
    }

}