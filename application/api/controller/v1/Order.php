<?php


namespace app\api\controller\v1;


use app\common\controller\Wxtools;
use think\Db;
use think\Validate;
use weixinzhifu\lib\WechatAppPay;

class Order extends AuthBase
{
    /**
     * 统一下单，WxPayUnifiedOrder中out_trade_no、body、total_fee、trade_type必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param WxPayUnifiedOrder $inputObj
     * @param int $timeOut
     * @return 成功时返回，其他抛异常
     * @throws WxPayException
     * 小程序统一下单
     * 结算订单
     * 用扣减余额的方式支付 如果失败选择 其他支付方式
     * 其他方式支付待商议0
     */
    public function payCarOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'out_trade_no' => 'require',
            'pay_type' => 'require',
        ], [
            'out_trade_no.require' => '请输入订单编号',
            'pay_type.require' => '请输入订单支付方式',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $orderInfo = Db::name('order')
            ->where('order_no', $params['out_trade_no'])
            ->where('status', 2)
            ->where('user_id', $this->user_id)
            ->field('discount_price,price,order_no,coupon_id')
            ->find();
        switch ($params['pay_type']) {
            case 1:
                $resultData = $this->miniAppPayOrder($orderInfo);
                $resultData['pay_type'] = $params['pay_type'];
                break;
            case 2:
                $result = $this->balanceAppPayOrder($orderInfo);
                if ($result) {
                    $resultData = [
                        'pay_type' => $params['pay_type'],
                        'out_trade_no' => $params['out_trade_no'],
                    ];
                }
                break;
            case 3:
                $this->couponPayOrder($orderInfo);
                break;
            case 4:
//                半折优惠券下单支付
                $this->discountCouponPayOrder($orderInfo);
                break;
        }
        echo $this->show(config('code.success'), '支付成功', []);
        die;
    }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 取消订单
     */
    public function cancelOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'out_trade_no' => 'require',
        ], [
            'out_trade_no.require' => '请输入订单编号',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        Db::name('order')
            ->where('order_no', $params['out_trade_no'])
            ->where('user_id', $this->user_id)
            ->where('status', 2)
            ->update(['status' => 6]);
        Db::name('order')
            ->where('order_no', $params['out_trade_no'])
            ->where('user_id', $this->user_id)
            ->where('status', 2)
            ->update(['status' => 6]);
        Db::name('order_log')
            ->where('order_no', $params['out_trade_no'])
            ->where('user_id', $this->user_id)
            ->update(['status' => 4]);

        echo $this->show(config('code.success'), '取消成功', []);
        die;
    }

    /**
     * 生成订单
     */
    public function makeOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'pay_user_id' => 'require',
            'project_ids' => 'require',
            'car_no' => 'require',
            'shop_id' => 'require',
        ], [
            'pay_user_id.require' => '请输入支付用户编号',
            'project_ids.require' => '请输入支付用户编号',
            'car_no.require' => '请选择车牌号',
            'shop_id.require' => '请选择所属门店',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }

        $order_prefix = config('wxconfig.prefix');
        $order_no = $order_prefix . get_orn();
        $prices = $this->sumOrderPrice($params, $order_no,$params['num']);
        if ($prices == false) {
            echo $this->show(config('code.error'), '余额不足,请提示客户充值', []);
            die;
        }
        $params['pay_type'] = 2;
        $num = count($params['project_ids']);
        $orderData = [
            'user_id' => $params['pay_user_id'],
            'car_no' => $params['car_no'],
            'pay_type' => $params['pay_type'],
            'order_no' => $order_no,
            'num' => $num,
            'projects' => $prices['projects'],
            'shop_id' => $params['shop_id'],
            'price' => $prices['price'],
            'discount_price' => $prices['discount_price'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $this->saveCarProjectOrder($orderData);
        echo $this->show(config('code.success'), '下单成功', []);
        die;
    }

    /**
     * 使用抵扣券生成订单
     * 全部用券 用券还要用钱 对半了
     *
     */
    public function makeOrderUseCoupon()
    {
        $params = input('post.');
        $validate = new Validate([
            'pay_user_id' => 'require',
            'coupon_code' => 'require',
            'coupon_id' => 'require',
            'car_no' => 'require',
            'shop_id' => 'require',
        ], [
            'pay_user_id.require' => '请输入支付用户编号',
            'coupon_code.require' => '请输入优惠券编码',
            'car_no.require' => '请选择车牌号',
            'shop_id.require' => '请选择所属门店',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $order_prefix = config('wxconfig.prefix');
        $order_no = $order_prefix . get_orn();
        $coupon = $this->checkUserCoupon($params['pay_user_id'], $params['coupon_id'], 1);
        if ($coupon == false) {
            echo $this->show(config('code.error'), '请检查抵扣券是否可用', ['code' => '202']);
            die;
        }
        $params['pay_type'] = 3;
        $num = 1;
        $orderData = [
            'user_id' => $params['pay_user_id'],
            'car_no' => $params['car_no'],
            'pay_type' => $params['pay_type'],
            'order_no' => $order_no,
            'num' => $num,
            'projects' => $coupon['projects'],
            'shop_id' => $params['shop_id'],
            'coupon_id' => $coupon['id'],
            'price' => 0,
            'discount_price' => $coupon['price'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $this->saveCarProjectOrder($orderData);
        Db::name('user_coupon')
            ->where('id', (int)$params['coupon_id'])
            ->where('user_id', $params['pay_user_id'])
            ->where('status', 2)
            ->update(['status' => 1, 'use_time' => date('Y-m-d H:i:s')]);
        $orderLogData = [
            'user_id' => $params['pay_user_id'],
            'order_no' => $order_no,
            'car_no' => $params['car_no'],
            'status' => 2,
            'project_name' => $coupon['projects'],
            'price' => 0,
            'server_time' => $coupon['server_time'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('order_log')->insert($orderLogData);
        echo $this->show(config('code.success'), '下单成功', []);
        die;
    }

    /**
     * 使用抵扣券生成订单
     * 全部用券 用券还要用钱 对半了
     * 半折券逻辑是对半不能用积分所有费
     *
     */
    public function makeOrderUseDiscountCoupon()
    {
        $params = input('post.');
        $validate = new Validate([
            'pay_user_id' => 'require',
            'coupon_id' => 'require',
            'car_no' => 'require',
            'shop_id' => 'require',
        ], [
            'pay_user_id.require' => '请输入支付用户编号',
            'coupon_id.require' => '请输入优惠券编码',
            'coupon_type.require' => '请输入优惠券类型',
            'car_no.require' => '请选择车牌号',
            'shop_id.require' => '请选择所属门店',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $coupon = $this->checkUserCoupon($params['pay_user_id'], $params['coupon_id'], 2);
        if ($coupon == false) {
            echo $this->show(config('code.error'), '请检查抵扣券是否可用', ['code' => '202']);
            die;
        }
        $order_prefix = config('wxconfig.prefix');
        $order_no = $order_prefix . get_orn();
        $prices = $this->sumDiscountCouponOrderPrice($params, $order_no);
        if ($prices == false) {
            echo $this->show(config('code.error'), '余额不足,请提示客户充值', []);
            die;
        }
        $order_prefix = config('wxconfig.prefix');
        $order_no = $order_prefix . get_orn();
        if ($prices == false) {
            echo $this->show(config('code.error'), '余额不足,请提示客户充值', []);
            die;
        }
        $params['pay_type'] = 4;
        $num = count($params['project_ids']);
        $orderData = [
            'user_id' => $params['pay_user_id'],
            'car_no' => $params['car_no'],
            'pay_type' => $params['pay_type'],
            'coupon_id' => $params['coupon_id'],
            'order_no' => $order_no,
            'num' => $num,
            'projects' => $prices['projects'],
            'shop_id' => $params['shop_id'],
            'price' => $prices['price'],
            'discount_price' => $prices['discount_price'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $this->saveCarProjectOrder($orderData);
        echo $this->show(config('code.success'), '下单成功', []);
        die;
    }

    /**
     *检测用户是否有此优惠券
     */
    public function checkUserCoupon($user_id, $coupon_id, $coupon_type)
    {
        $coupon = Db::name('user_coupon')
            ->where('id', (int)$coupon_id)
            ->where('user_id', $user_id)
            ->where('status', 2)
            ->field('id,project_name,coupon_code,project_id,coupon_type')
            ->find();
        if (empty($coupon)) {
            return false;
        }
        if ($coupon['coupon_type'] != $coupon_type) {
            return false;
        }
        $project = Db::name('project')
            ->where('id', $coupon['project_id'])
            ->field('project_name,server_time,price')
            ->find();
        $coupon['projects'] = $project['project_name'];
        $coupon['server_time'] = $project['server_time'];
        $coupon['price'] = $project['price'];
        return $coupon;

    }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 支付会员费用
     * 会员支付费用
     */
    public function payVipOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'pay_type' => 'require',
        ], [
            'pay_type.require' => '请输入订单支付方式',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $vipInfo = Db::name('vip')
            ->where('status', 1)
            ->field('price,const_price')
            ->find();
        switch ($params['pay_type']) {
            case 1:
                $resultData = $this->miniAppVipPayOrder($vipInfo);
                break;
            case 2:
                $result = $this->balanceVipPayOrder($vipInfo);
                if ($result) {
                    $resultData = [
                        'pay_type' => $params['pay_type'],
                        'out_trade_no' => $result['out_trade_no'],
                    ];
                }
                echo $this->show(config('code.success'), '支付成功', $resultData);
                die;
                break;
        }
        echo $this->show(config('code.success'), '下单成功', $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 兑换会员
     */
    public function exchangeVip()
    {
        $params = input('post.');
        $validate = new Validate([
            'vip_code' => 'require',
        ], [
            'vip_code.require' => '请输入会员兑换码',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $valid_day = Db::name('vip_code')
            ->where('status', 2)
            ->where('code', strtoupper($params['vip_code']))
            ->value('valid_day');
        if (empty($valid_day)) {
            echo $this->show(config('code.error'), '兑换失败,如有疑问请联系客服', []);
            die;
        }
        $updata = [
            'user_id' => $this->user_id,
            'exchange_time' => date('Y-m-d H:i:s'),
            'status' => 1,
        ];
        Db::name('vip_code')
            ->where('status', 2)
            ->where('code', strtoupper($params['vip_code']))
            ->update($updata);
        $order_prefix = config('wxconfig.prefix');
        $order_no = $order_prefix . get_orn();
        $vipOrderInfo = [
            'user_id' => $this->user_id,
            'pay_type' => 3,
            'price' => '',
            'order_no' => $order_no,
            'status' => 1,
            'pay_time' => date('Y-m-d H:i:s'),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('vip_order')->insert($vipOrderInfo);
        $vip_time_out = Db::name('user')
            ->where('id', $this->user_id)
            ->value('vip_time_out');
        if ($vip_time_out < time()) {
            $vip_time_out = time();
        }
//      之前的天数续费
        $vip_time_out = strtotime("+$valid_day day", $vip_time_out);
        Db::name('user')
            ->where('id', $this->user_id)
            ->update(['vip_time_out' => $vip_time_out]);
        $this->saveCoupon(1, '洗车');
//        增加会员时常
        echo $this->show(config('code.success'), '兑换成功', []);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 兑换优惠券
     */
    public function exchangeCoupon()
    {
        $params = input('post.');
        $validate = new Validate([
            'coupon_code' => 'require',
        ], [
            'coupon_code.require' => '请输入优惠券兑换码',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $couponInfo = Db::name('coupon_code')
            ->where('status', 2)
            ->where('code', strtoupper($params['coupon_code']))
            ->field('project_name,project_id,coupon_type')
            ->find();
        if (empty($couponInfo)) {
            echo $this->show(config('code.error'), '兑换失败', []);
            die;
        }
        $savedata = [
            'user_id' => $this->user_id,
            'coupon_code' => $params['coupon_code'],
            'project_name' => $couponInfo['project_name'],
            'project_id' => $couponInfo['project_id'],
            'coupon_type' => $couponInfo['coupon_type'],
            'status' => 2,
            'type' => 2,
            'time_out' => strtotime("+30 day", time()),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
//        优惠券已兑换
        Db::name('coupon_code')
            ->where('status', 2)
            ->where('code', strtoupper($params['coupon_code']))
            ->update(['status' => 1]);

        Db::name('user_coupon')->insert($savedata);
//        增加会员时常
        echo $this->show(config('code.success'), '兑换成功', []);
        die;
    }

    /**
     * 立即充值 余额充值
     * 1 微信支付  2 线下充值
     * TODO 送积分暂定
     * 00
     *  冲1000，送100积分 / 冲3000, 送500积分 / 冲5000，送1000积分 ，充值不到标准线的，往下靠，比如，冲2000，没到3000，就按照1000的标准，送100积分
     */
    public function rechargeOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'recharge_price' => 'require',
        ], [
            'recharge_price.require' => '请选择充值金额',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }

        if ($params['recharge_price'] > 50000) {
            echo $this->show(config('code.error'), '单次充值金额已达上限', ['code' => '202']);
            die;
        }
        $coupon_balance=0;
        if (time() < 1648742399) {
            if (2999 < $params['recharge_price'] && $params['recharge_price'] < 6000) {
                $coupon_balance = 150;
            }
            if (5999 < $params['recharge_price'] && $params['recharge_price'] < 10000) {
                $coupon_balance = 600;
            }
            if (9999 <= $params['recharge_price'] && $params['recharge_price'] < 20000) {
                $coupon_balance = 1500;
            }
            if (19999 <= $params['recharge_price'] && $params['recharge_price'] < 100000) {
                $coupon_balance = 4000;
            }
        }
        $openid = $this->openid;
        $timeOut = 30;
        $datas = array();
        $order_prefix = config('wxconfig.prefix');
        $out_trade_no = $order_prefix . get_orn();
        $total_fee = $params['recharge_price'];
        $WxObj = new Wxtools();
        $datas['body'] = config('wxconfig.body');
        $datas['out_trade_no'] = $out_trade_no;//订单号
        $datas['total_fee'] = $total_fee * 100;
        $datas['time_start'] = date("YmdHis");
        $datas['time_expire'] = date("YmdHis", time() + 600);
        $datas['notify_url'] = config('wxconfig.mini_notify_url');
        $datas['trade_type'] = 'JSAPI';
        $datas['openid'] = $openid;
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $datas['appid'] = config('wxconfig.miniAppId');//小程序账号ID
        $datas['mch_id'] = config('wxconfig.sp_mchid');//微信商户号
        $datas['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];//ip
        $datas['nonce_str'] = $WxObj->getNonceStr();//随机字符串
        //签名步骤一：按字典序排序参数
        ksort($datas);
        $string = $WxObj->ToUrlParamss($datas);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . config('wxconfig.key');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        $datas['sign'] = $result;//签名
        $xml = $WxObj->ToXml($datas);
        $response = $WxObj->postXmlCurl($xml, $url, false, $timeOut);
        $data = $WxObj->FromXml($response);
        $resultData = $this->GetJsApiParameters($data);
        $orderInfo = [
            'user_id' => $this->user_id,
            'order_no' => $out_trade_no,
            'price' => $total_fee,
            'coupon_balance' => $coupon_balance,
            'pay_type' => 1,
            'status' => 2,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
//        保存充值订单
        $this->saveRechargeOrder($orderInfo);
        $resultData['out_trade_no'] = $out_trade_no;
        echo $this->show(config('code.success'), '下单成功请前往支付', $resultData);
        die;
    }

    /**
     * 立即充值 余额充值
     * 店长帮忙充值
     * TODO 送积分暂定
     */
    public function shopRechargeOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'recharge_price' => 'require',
            'user_id' => 'require',
        ], [
            'recharge_price.require' => '请选择充值金额',
            'user_id.require' => '请选择充值对象',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        if ($params['recharge_price'] > 50000) {
            echo $this->show(config('code.error'), '单次充值金额已达上限', ['code' => '202']);
            die;
        }
        $coupon_balance=0;
        if (time() < 1648742399) {
            if (2999 < $params['recharge_price'] && $params['recharge_price'] < 6000) {
                $coupon_balance = 150;
            }
            if (5999 < $params['recharge_price'] && $params['recharge_price'] < 10000) {
                $coupon_balance = 600;
            }
            if (9999 <= $params['recharge_price'] && $params['recharge_price'] < 20000) {
                $coupon_balance = 1500;
            }
            if (19999 <= $params['recharge_price'] && $params['recharge_price'] < 100000) {
                $coupon_balance = 4000;
            }
        }

        $order_prefix = config('wxconfig.prefix');
        $out_trade_no = $order_prefix . get_orn();
        $total_fee = $params['recharge_price'];
        $orderInfo = [
            'user_id' => $params['user_id'],
            'order_no' => $out_trade_no,
            'price' => $total_fee,
            'coupon_balance' => $coupon_balance,
            'pay_type' => 2,
            'status' => 1,
            'recharge_user_id' => $this->user_id,
            'pay_time' => date('Y-m-d H:i:s'),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $userinfo = Db::name('userinfo')
            ->where('user_id', $params['user_id'])
            ->field('balance,coupon_balance')
            ->find();
        $updata = [
            'balance' => $total_fee + $userinfo['balance'],
            'coupon_balance' => $coupon_balance + $userinfo['coupon_balance'],
        ];
        Db::name('userinfo')
            ->where('user_id', $params['user_id'])
            ->update($updata);
//        保存充值订单
        $this->saveRechargeOrder($orderInfo);
        echo $this->show(config('code.success'), '充值成功', []);
        die;
    }

    /**
     * @param $orderinfo
     * @return json数据，可直接填入js函数作为参数
     * 余额支付
     * 支付时间修改 订单状态修改 用户余额积分修改等操作
     * 余额支付
     * 支付完成以后会员日期往后加一年
     */
    private function balanceVipPayOrder($vipInfo)
    {
        $userinfo = Db::name('userinfo')
            ->where('user_id', $this->user_id)
            ->field('balance,coupon_balance')
            ->find();
        $vipPrice = $vipInfo['const_price'];
        if ($userinfo['balance'] < $vipPrice) {
            echo $this->show(config('code.error'), '余额不足', []);
            die;
        }
        $coupon_balance = 0;//只能用余额支付
        $coupon_balance = $userinfo['coupon_balance'] > $coupon_balance ? $coupon_balance : $userinfo['coupon_balance'];
        $balance = $vipPrice - $coupon_balance;
        $order_prefix = config('wxconfig.prefix');
        $order_no = $order_prefix . get_orn();
        $vipOrderInfo = [
            'user_id' => $this->user_id,
            'pay_type' => 2,
            'price' => $vipInfo['price'],
            'order_no' => $order_no,
            'status' => 1,
            'pay_time' => date('Y-m-d H:i:s'),
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('vip_order')->insert($vipOrderInfo);
        $vip_time_out = Db::name('user')
            ->where('id', $this->user_id)
            ->value('vip_time_out');
        if ($vip_time_out < time()) {
            $vip_time_out = time();
        }
//      之前的天数续费
        $vip_time_out = strtotime("+1year", $vip_time_out);
        Db::name('user')
            ->where('id', $this->user_id)
            ->update(['vip_time_out' => $vip_time_out]);
        $updata = [
            'balance' => $userinfo['balance'] - $balance,
            'coupon_balance' => $userinfo['coupon_balance'] - $coupon_balance,
        ];
        Db::name('userinfo')
            ->where('user_id', $this->user_id)
            ->update($updata);
        $saleLogData = [
            'user_id' => $this->user_id,
            'order_no' => $order_no,
            'price' => $vipInfo['price'],
            'type' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $this->saveCoupon(1, '洗车');
        $this->saleLog($saleLogData);
//        修改用户
//        服务已结束
        return true;
    }

    /**
     * @param $orderinfo
     * @return json数据，可直接填入js函数作为参数
     * 余额支付
     * 支付时间修改 订单状态修改 用户余额积分修改等操作
     * 详情订单信息
     */
    private function balanceAppPayOrder($orderInfo)
    {
        $balance = $orderInfo['price'];
        $coupon_balance = $orderInfo['discount_price'];
        $userinfo = Db::name('userinfo')
            ->where('user_id', $this->user_id)
            ->field('balance,consume_count,coupon_balance')
            ->find();
        if ($userinfo['balance'] < $balance) {
            echo $this->show(config('code.error'), '余额不足', []);
            die;
        }
        $updata = [
            'balance' => $userinfo['balance'] - $balance,
            'coupon_balance' => $userinfo['coupon_balance'] - $coupon_balance,
            'consume_count' => $userinfo['consume_count'] + 1,
        ];
        Db::name('userinfo')
            ->where('user_id', $this->user_id)
            ->update($updata);
        Db::name('order')
            ->where('order_no', $orderInfo['order_no'])
            ->update(['status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);

        // 服务已结束
        $orderLog = Db::name('order_log')
            ->where('order_no', $orderInfo['order_no'])
            ->where('status', 2)
            ->field('server_time,id')
            ->select();
        foreach ($orderLog as $item) {
            $data = [
                'status' => 1,
                'end_time' => time() + $item['server_time'] * 60,
                'update_time' => date('Y-m-d H:i:s'),
            ];
            Db::name('order_log')
                ->where('id', $item['id'])
                ->update($data);
        }
        $saleLogData = [
            'user_id' => $this->user_id,
            'order_no' => $orderInfo['order_no'],
            'price' => $balance,
            'type' => 2,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $this->saleLog($saleLogData);
        return true;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 小程序支付
     */
    private function miniAppPayOrder($orderinfo)
    {
        $openid = $this->openid;
        $timeOut = 30;
        $datas = array();
        $WxObj = new Wxtools();
        $datas['body'] = config('wxconfig.body');
        $datas['out_trade_no'] = $orderinfo['order_no'];//订单号
        $datas['total_fee'] = $orderinfo['price'] * 100;
        $datas['time_start'] = date("YmdHis");
        $datas['time_expire'] = date("YmdHis", time() + 600);
        $datas['notify_url'] = config('wxconfig.mini_notify_url');
        $datas['trade_type'] = 'JSAPI';
        $datas['openid'] = $openid;
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $datas['appid'] = config('wxconfig.miniAppId');//小程序账号ID
        $datas['mch_id'] = config('wxconfig.sp_mchid');//微信商户号
        $datas['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];//ip
        $datas['nonce_str'] = $WxObj->getNonceStr();//随机字符串
        //签名步骤一：按字典序排序参数
        ksort($datas);
        $string = $WxObj->ToUrlParamss($datas);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . config('wxconfig.key');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        $datas['sign'] = $result;//签名
        $xml = $WxObj->ToXml($datas);
        $response = $WxObj->postXmlCurl($xml, $url, false, $timeOut);
        $data = $WxObj->FromXml($response);
        $resultData = $this->GetJsApiParameters($data);
        $resultData['out_trade_no'] = $orderinfo['order_no'];
        return $resultData;

    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 小程序会员支付
     */
    private function miniAppVipPayOrder($vipInfo)
    {
        $order_prefix = config('wxconfig.prefix');
        $order_no = $order_prefix . get_orn();
        $openid = $this->openid;
        $timeOut = 30;
        $datas = array();
        $WxObj = new Wxtools();
        $datas['body'] = config('wxconfig.body');
        $datas['out_trade_no'] = $order_no;//订单号
        $datas['total_fee'] = $vipInfo['price'] * 100;
        $datas['time_start'] = date("YmdHis");
        $datas['time_expire'] = date("YmdHis", time() + 600);
        $datas['notify_url'] = config('wxconfig.mini_notify_url');
        $datas['trade_type'] = 'JSAPI';
        $datas['openid'] = $openid;
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $datas['appid'] = config('wxconfig.miniAppId');//小程序账号ID
        $datas['mch_id'] = config('wxconfig.sp_mchid');//微信商户号
        $datas['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];//ip
        $datas['nonce_str'] = $WxObj->getNonceStr();//随机字符串
        //签名步骤一：按字典序排序参数
        ksort($datas);
        $string = $WxObj->ToUrlParamss($datas);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . config('wxconfig.key');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        $datas['sign'] = $result;//签名
        $xml = $WxObj->ToXml($datas);
        $response = $WxObj->postXmlCurl($xml, $url, false, $timeOut);
        $data = $WxObj->FromXml($response);
        $resultData = $this->GetJsApiParameters($data);
        $resultData['out_trade_no'] = $order_no;
        $resultData['pay_type'] = 1;
        $vipOrderInfo = [
            'user_id' => $this->user_id,
            'pay_type' => 1,
            'price' => $vipInfo['price'],
            'order_no' => $order_no,
            'status' => 2,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('vip_order')->insert($vipOrderInfo);
        return $resultData;
    }

    /**
     * @param $orderInfo
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 其他方式支付 半价折扣券
     * 修改对折券的状态
     */
    private function discountCouponPayOrder($orderInfo)
    {
        $balance = $orderInfo['price'];
        $coupon_id = $orderInfo['coupon_id'];
        Db::name('user_coupon')
            ->where('id', $coupon_id)
            ->where('user_id', $this->user_id)
            ->update(['status' => 1]);
        $userinfo = Db::name('userinfo')
            ->where('user_id', $this->user_id)
            ->field('balance,consume_count')
            ->find();
        if ($userinfo['balance'] < $balance) {
            echo $this->show(config('code.error'), '余额不足', []);
            die;
        }
        $updata = [
            'balance' => $userinfo['balance'] - $balance,
            'consume_count' => $userinfo['consume_count'] + 1,
        ];
        Db::name('userinfo')
            ->where('user_id', $this->user_id)
            ->update($updata);
        Db::name('order')
            ->where('order_no', $orderInfo['order_no'])
            ->update(['status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);

        // 服务已结束
        $orderLog = Db::name('order_log')
            ->where('order_no', $orderInfo['order_no'])
            ->where('status', 2)
            ->field('server_time,id')
            ->select();
        foreach ($orderLog as $item) {
            $data = [
                'status' => 1,
                'end_time' => time() + $item['server_time'] * 60,
                'update_time' => date('Y-m-d H:i:s'),
            ];
            Db::name('order_log')
                ->where('id', $item['id'])
                ->update($data);
        }
        $saleLogData = [
            'user_id' => $this->user_id,
            'order_no' => $orderInfo['order_no'],
            'price' => $balance,
            'type' => 2,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $this->saleLog($saleLogData);

        return true;
    }

    /**
     * @param $orderInfo
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 优惠券支付
     * 用户确认支付完成以后修改优惠券状态
     */
    private function couponPayOrder($orderInfo)
    {
        Db::name('order')
            ->where('order_no', $orderInfo['order_no'])
            ->update(['status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);
//        服务进行中
        $server_time = Db::name('order_log')
            ->where('order_no', $orderInfo['order_no'])
            ->value('server_time');
        Db::name('order_log')
            ->where('order_no', $orderInfo['order_no'])
            ->update(['status' => 1, 'end_time' => time() + $server_time * 60,]);
        return true;
    }

    /**
     * 1. 首先判断是不是会员
     * 会员可以享受会员价格
     * 计算总共的价格 判断项目的支付金额
     *  赠送的积分使用用完
     */
    private function sumOrderPrice($params, $order_no, $bdnum)
    {
        $balance = Db::name('userinfo')
            ->where('user_id', $params['pay_user_id'])
            ->field('coupon_balance,balance')
            ->find();
        $vip_time_out = Db::name('user')
            ->where('id', $params['pay_user_id'])
            ->value('vip_time_out');
        $projectInfo = Db::name('project')
            ->whereIn('id', $params['project_ids'])
            ->field('project_name,vip_price,server_time,price,is_num,price_type')
            ->select();
        $orderlogData = [];
        $allPrice = 0;
        $projects = [];
        foreach ($projectInfo as $key => $value) {
            $price = $value['price'];
            if ($vip_time_out > time()) {
                $price = $value['vip_price'];
            }
            $projects[] = $value['project_name'];
            if ($value['is_num'] == 1) {
                if ($value['price_type'] == 1) {
                    $allPrice += ($price * $bdnum);
                } else {
                    $allPrice -= ($price * $bdnum);
                }
            } else {
                $allPrice += $price;
            }
            $orderlogData[$key]['user_id'] = $params['pay_user_id'];
            $orderlogData[$key]['order_no'] = $order_no;
            $orderlogData[$key]['status'] = 2;
            $orderlogData[$key]['car_no'] = $params['car_no'];
            $orderlogData[$key]['server_time'] = $value['server_time'];
            $orderlogData[$key]['project_name'] = $value['project_name'];
            $orderlogData[$key]['price'] =$value['is_num']==1? $price*$bdnum:$price;
            $orderlogData[$key]['create_time'] = date('Y-m-d H:i:s');
            $orderlogData[$key]['update_time'] = date('Y-m-d H:i:s');
        }

        $discount_price = $allPrice * 0.3 > $balance['coupon_balance'] ? $balance['coupon_balance'] : $allPrice * 0.3;
        $pay_price = $allPrice - $discount_price;
        $data = [
            'price' => $pay_price,
            'discount_price' => $discount_price,
            'projects' => implode('、', $projects)
        ];
//        账户余额不足
        if ($params['pay_type'] == 2 && $pay_price > $balance['balance']) {
            return false;
        }
        Db::name('order_log')->insertAll($orderlogData);
        return $data;
    }

    /**
     * @param $params
     * @param $order_no
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 计算半折优惠卡价格0
     */
    private function sumDiscountCouponOrderPrice($params, $order_no)
    {
        $balance = Db::name('userinfo')
            ->where('user_id', $params['pay_user_id'])
            ->value('balance');
        $vip_time_out = Db::name('user')
            ->where('id', $params['pay_user_id'])
            ->value('vip_time_out');
        $projectInfo = Db::name('project')
            ->whereIn('id', $params['project_ids'])
            ->field('project_name,vip_price,server_time,price')
            ->select();
        $orderlogData = [];
        $allPrice = 0;
        $projects = [];
        foreach ($projectInfo as $key => $value) {
            $price = $value['price'] * 0.5;
            if ($vip_time_out > time()) {
                $price = $value['vip_price'] * 0.5;
            }
            $projects[] = $value['project_name'];
            $allPrice += $price;
            $orderlogData[$key]['user_id'] = $params['pay_user_id'];
            $orderlogData[$key]['order_no'] = $order_no;
            $orderlogData[$key]['status'] = 2;
            $orderlogData[$key]['car_no'] = $params['car_no'];
            $orderlogData[$key]['server_time'] = $value['server_time'];
            $orderlogData[$key]['project_name'] = $value['project_name'];
            $orderlogData[$key]['price'] = $price;
            $orderlogData[$key]['create_time'] = date('Y-m-d H:i:s');
            $orderlogData[$key]['update_time'] = date('Y-m-d H:i:s');
        }
        $pay_price = $allPrice;
        $data = [
            'price' => $pay_price,
            'discount_price' => $pay_price,//对半抵扣金额
            'projects' => implode('、', $projects)
        ];
//        账户余额不足
        if ($params['pay_type'] == 4 && $pay_price > $balance['balance']) {
            return false;
        }
        Db::name('order_log')->insertAll($orderlogData);
        return $data;
    }

    /**
     * 用户确认下单情况
     * 用户核对订单
     * 确认完成以后待结算状态服务中也是待结算状态
     * 用户端展示待结算 对外是服务中 服务中待结算
     * TODO 暂时废弃
     */
    public function userCheckOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'out_trade_no' => 'require',
        ], [
            'out_trade_no.require' => '请选择订单',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        Db::name('order')
            ->where('order_no', $params['out_trade_no'])
            ->where('user_id', $this->user_id)
            ->update(['status' => 1]);
        Db::name('order_log')
            ->where('order_no', $params['out_trade_no'])
            ->where('user_id', $this->user_id)
            ->update(['status' => 1]);

        echo $this->show(config('code.success'), '确认成功', []);
        die;
    }

    /**
     * 微信查询结果
     * 查询完成之后修改状态
     * 支付成功之后的操作具体有
     *  支付完成查询订单
     * 支付成功增加对应的道具次数
     * 查询用户是否有此道具没有新增条数
     * 查询充值订单
     *
     */
    public function queryRechargeOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'out_trade_no' => 'require',
        ], [
            'out_trade_no.require' => '请传入订单号',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $out_trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';
        $pay = new WechatAppPay();
        $resquery = $pay->orderQuery($out_trade_no);
        if ($resquery['return_code'] == "SUCCESS") {
            if (isset($resquery['trade_state']) && $resquery['trade_state'] == 'SUCCESS') {
                $total_fee = $resquery['total_fee'];
                $orderInfo = Db::name('recharge')
                    ->where('order_no', $out_trade_no)
                    ->where('user_id', $this->user_id)
                    ->field('user_id,coupon_balance,price,status')
                    ->find();
                if ($total_fee != $orderInfo['price'] * 100) {
                    echo $this->show(config('code.error'), '支付金额与订单金额不符', ['code' => '200']);
                    die;
                }
//               待支付状态处理
                switch ($orderInfo['status']) {
                    case 1:
                        echo $this->show(config('code.success'), '支付成功', ['code' => '200']);
                        die;
                        break;
                    case 2:
                        Db::name('recharge')
                            ->where('order_no', $out_trade_no)
                            ->where('user_id', $this->user_id)
                            ->update(['status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);
//                        修改用户余额以及积分
                        $userinfo = Db::name('userinfo')
                            ->where('user_id', $this->user_id)
                            ->field('balance,coupon_balance')
                            ->find();
                        $updata = [
                            'balance' => $orderInfo['price'] + $userinfo['balance'],
                            'coupon_balance' => $orderInfo['coupon_balance'] + $userinfo['coupon_balance'],
                        ];
                        Db::name('userinfo')
                            ->where('user_id', $this->user_id)
                            ->update($updata);
                        echo $this->show(config('code.success'), '充值成功', ['code' => '200']);
                        die;
                        break;
                    default:
                        echo $this->show(config('code.error'), $resquery['trade_state_desc'], ['code' => '202']);
                        die;
                        break;
                }
            }
        }
        echo $this->show(config('code.error'), '支付失败', ['code' => '203']);
        die;
    }

    /**
     * 保存充值订单
     */
    private function saveRechargeOrder($orderInfo)
    {
        Db::name('recharge')->insert($orderInfo);
        return true;

    }

    /**
     * 保存下单记录
     * 顺便保存订单排队状态
     * 订单保留时间五分钟
     */
    private function saveCarProjectOrder($orderInfo)
    {
        Db::name('order')->insert($orderInfo);
        return true;
    }

    /**
     * 获取jsapi支付的参数
     * @param array $UnifiedOrderResult 统一支付接口返回的数据
     * @return json数据，可直接填入js函数作为参数
     */
    public function GetJsApiParameters($UnifiedOrderResult)
    {
        if (!array_key_exists('appid', $UnifiedOrderResult)
            || !array_key_exists("prepay_id", $UnifiedOrderResult)
            || $UnifiedOrderResult['prepay_id'] == ''
        ) {
            echo $this->show(config('code.error'), '下单失败请重新支付', ['code' => 200]);
            die;
        }
        $WxObj = new Wxtools();
        $da = array();
        $da['appId'] = $UnifiedOrderResult['appid'];
        $timeStamp = (string)time();
        $da['timeStamp'] = $timeStamp;
        $da['nonceStr'] = $WxObj->getNonceStr();
        $da['package'] = 'prepay_id=' . $UnifiedOrderResult['prepay_id'];
        $da['signType'] = 'MD5';
        //签名步骤一：按字典序排序参数
        ksort($da);
        $string = $WxObj->ToUrlParamss($da);
        //签名步骤二：在string后加入KEY
        $string = $string . '&key=' . config('wxconfig.key');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        $da['paySign'] = $result;
        return $da;
    }

    /**
     * 微信查询结果
     * 查询完成之后修改状态
     * 支付成功之后的操作具体有
     *  支付完成查询订单
     * 支付成功增加对应的道具次数
     * 查询用户是否有此道具没有新增条数
     *
     */
    public function queryVipOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'out_trade_no' => 'require',
        ], [
            'out_trade_no.require' => '请传入订单号',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $out_trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';
        $pay = new WechatAppPay();
        $resquery = $pay->orderQuery($out_trade_no);
        if ($resquery['return_code'] == "SUCCESS") {
            if (isset($resquery['trade_state']) && $resquery['trade_state'] == 'SUCCESS') {
                $orderInfo = Db::name('vip_order')
                    ->where('order_no', $out_trade_no)
                    ->where('user_id', $this->user_id)
                    ->field('user_id,status,price')
                    ->find();
//               待支付状态处理
                switch ($orderInfo['status']) {
                    case 1:
                        echo $this->show(config('code.success'), '支付成功', ['code' => '200']);
                        die;
                        break;
                    case 2:
                        Db::name('vip_order')
                            ->where('order_no', $out_trade_no)
                            ->where('user_id', $this->user_id)
                            ->update(['status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);
                        $vip_time_out = Db::name('user')
                            ->where('id', $this->user_id)
                            ->value('vip_time_out');
                        if ($vip_time_out < time()) {
                            $vip_time_out = time();
                        }
//                    之前的天数续费
                        $vip_time_out = strtotime("+1year", $vip_time_out);
                        Db::name('user')
                            ->where('id', $this->user_id)
                            ->update(['vip_time_out' => $vip_time_out]);
                        echo $this->show(config('code.success'), '支付成功', ['code' => '200']);
                        die;
                        break;
                    default:
                        echo $this->show(config('code.error'), $resquery['trade_state_desc'], ['code' => '202']);
                        die;
                        break;
                }
            }
        }
        echo $this->show(config('code.error'), '支付失败', ['code' => '203']);
        die;
    }

    /**
     * 微信查询结果
     * 查询完成之后修改状态
     * 支付成功之后的操作具体有
     *  支付完成查询订单
     * 支付成功增加对应的道具次数
     * 查询用户是否有此道具没有新增条数
     *
     */
    public function queryCarOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'out_trade_no' => 'require',
        ], [
            'out_trade_no.require' => '请传入订单号',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $out_trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';
        $pay = new WechatAppPay();
        $resquery = $pay->orderQuery($out_trade_no);
        if ($resquery['return_code'] == "SUCCESS") {
            if (isset($resquery['trade_state']) && $resquery['trade_state'] == 'SUCCESS') {
                $orderInfo = Db::name('order')
                    ->where('order_no', $out_trade_no)
                    ->where('user_id', $this->user_id)
                    ->field('user_id,status,price')
                    ->find();
//               待支付状态处理
                switch ($orderInfo['status']) {
                    case 1:
                        echo $this->show(config('code.success'), '支付成功', ['code' => '200']);
                        die;
                        break;
                    case 2:
                        Db::name('order')
                            ->where('order_no', $out_trade_no)
                            ->where('user_id', $this->user_id)
                            ->update(['status' => 5, 'pay_time' => date('Y-m-d H:i:s')]);
                        Db::name('order_log')
                            ->where('order_no', $out_trade_no)
                            ->where('user_id', $this->user_id)
                            ->update(['status' => 3]);
                        Db::name('userinfo')
                            ->where('user_id', $this->user_id)
                            ->setInc('consume_count', 1);
                        echo $this->show(config('code.success'), '支付成功', ['code' => '200']);
                        die;
                        break;
                    default:
                        echo $this->show(config('code.error'), $resquery['trade_state_desc'], ['code' => '202']);
                        die;
                        break;
                }
            }
        }
        echo $this->show(config('code.error'), '支付失败', ['code' => '203']);
        die;
    }
}