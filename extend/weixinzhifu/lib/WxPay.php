<?php

namespace weixinzhifu\lib;

use app\api\controller\v1\AuthBase;
use think\Request;
use think\Db;
use app\api\model\Creditforusertrade;
use app\api\model\Creditforuserpay;

class WxPay
{
    public function wechat($data, $id)
    {
        /**
         * 首先生产预支付订单信息
         * 配置微信的相关参数 和接受客户端提交的参数
         */
        include 'WechatAppPay.php';
        //填写配置参数
        $options = array(
            'appid' => 'wx5eefd984623d1910',    //填写微信分配的公众账号ID
            'mch_id' => config('pay.weixin')['mch_id'],             //填写微信支付分配的商户号
            'notify_url' => config('pay.weixin')['notify_url'],  //填写微信支付结果回调地址
            'key' => config('pay.weixin')['key']       //填写微信商户支付密钥
        );
        //初始化配置
        $wechatAppPay = new wechatAppPay($options);
        //客户端需要提交xml格式的数据服务端向微信服务器发起请求
        $total_fee = floatval($data['money']);
//      dump( $total_fee);die;
        //下单必要的参数
        $params['body'] = '抢单王k币充值';    //商品描述
        $params['out_trade_no'] = get_orn();//自定义的订单号
        $params['total_fee'] = $total_fee;//订单金额 只能为整数 单位为分
        $params['nonce_str'] = uniqid();//随机数
        $params['spbill_create_ip'] = $this->get_client_ip();
        $params['trade_type'] = 'APP';             //交易类型 JSAPI | NATIVE | APP | WAP
        //统一下单
        $result = $wechatAppPay->unifiedOrder($params);
        //创建APP端预支付参数
        $data = $wechatAppPay->getAppPayParams($result);
        //sign 加密之后返回给客户端
        $savedata = [
            'tradeno' => get_orn(),
            'userid' => $id,
            'trademoney' => $total_fee,
            'tradeoldmoney' => $total_fee,
            'tradestatus' => 0,
            'paytype' => 1,
            'addtime' => date('Y-m-d H:m:s')];
        $tradeid = Db::name('Creditforusertrade')->insertGetId($savedata);
        $obj = new Creditforuserpay();
        $obj->data([
            'userid' => $id,
            'paymoney' => $total_fee,
            'oldmoney' => $total_fee,
            'tradeno' => get_orn(),
            'tradeid' => $tradeid,
            'stype' => 1,
            'paytype' => 1,
            'addtime' => date('Y-m-d H:m:s'),
        ]);
        $obj->save();
        echo show(config('code.success'), '统一下单预支付参数', $data);
        die;
    }

    /**
     * @return array|false|string
     * 获取服务端iP
     */
    public function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "127.0.0.1";
        }
        return $cip;
    }
}