<?php


namespace app\admin\controller\v1;


use app\common\controller\Wxtools;
use think\Db;
use think\Validate;

class Order extends Base
{
    /**
     * 订单列表
     * 废弃
     */
    public function getOrderList()
    {

    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 导出订单
     */
    public function exportSaleOrder()
    {
        $params = input('post.');
        $result = Db::name('order');
//        支付方式
        if (!empty($params['pay_type'])) {
            $result->where('pay_type', $params['pay_type']);
        }
//        创建时间
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
        }
//        支付状态
        if (!empty($params['status'])) {
            $status = (int)$params['status'];
            $result->where('status', $status);
        } else {
            $result->whereIn('status', [1, 2, 3, 4, 5, 6]);

        }
        $ids = $result
            ->order('id', 'desc')
            ->column('id');
        if (empty($ids)) {
            $data = [];
            echo $this->show(config('code.success'), '导出订单列表', $data);
            die;
        }
        $resultDatas = $result->field('update_time', true)
            ->whereIn('id', $ids)
            ->order('id', 'desc')
            ->select();
        if (!empty($resultDatas)) {
            foreach ($resultDatas as $v) {
                $userIds[] = $v['user_id'];
            }
        }
        $userIds = array_unique($userIds);
        $userinfos = Db::name('userinfo')
            ->whereIn('user_id', $userIds)
            ->field('user_id,nickname,headimgurl,mobile,name')
            ->select();
        foreach ($resultDatas as &$resultData) {
            $resultData['user_name'] = '';
            $resultData['mobile'] = '';
            foreach ($userinfos as $userinfo) {
                if ($resultData['user_id'] == $userinfo['user_id']) {
                    $resultData['nickname'] = $userinfo['nickname'];
                    $resultData['headimgurl'] =  $userinfo['headimgurl'];
                    $resultData['mobile'] = $userinfo['mobile'];
                }
            }
        }
        echo $this->show(config('code.success'), '导出订单列表', $resultDatas);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取订单详情
     */
    public function getOrderDetial()
    {
        $params = input('post.');
        $validate = new Validate([
            'order_no' => 'require',
        ], [
            'order_no.require' => '请传入订单编号',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }

        $orderDetial = Db::name('order_log')
            ->where('order_no', $params['order_no'])
            ->field('user_id,update_time', true)
            ->select();
        echo $this->show(config('code.success'), '订单明细', $orderDetial);
        unset($upData, $params);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除订单信息
     */
    public function delOrder()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
        ], [
            'id.require' => '订单id',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        Db::name('order')
            ->where('id', $params['id'])
            ->delete();
        echo $this->show(config('code.success'), '删除成功', '');
        unset($upData, $params);
        die;


    }

    /**
     * 微信退款
     * 退款金额 原始订单号
     */
    public function wx_return_money()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'order_no' => 'require',
            'user_id' => 'require',
        ], [
            'id.require' => '订单记录编号id',
            'order_no.require' => '请选择订单号',
            'user_id.require' => '请选择用户',
            'price.require' => '请填写退款金额',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        $APPID = config('wxconfig.miniAppId');
        $MCHID = config('wxconfig.sp_mchid');
        $WxObj = new Wxtools();
        $Nonce_str = $WxObj->getNonceStr();//随机字符串
        $out_trade_nor = $params['order_no'];//原始订单哈
        $out_refund_no = 'QSR' . get_orn();//退单号
        $data =
            [
                'appid' => $APPID,
                'mch_id' => $MCHID,
                'nonce_str' => $Nonce_str,
                'out_trade_no' => $out_trade_nor,
                'out_refund_no' => $out_refund_no,
                'total_fee' => $params['price'] * 100,
                'refund_fee' => $params['price'] * 100,
            ];
        $xmlparams = $WxObj->SetSign($data);
        $xml = $WxObj->postStr($url, $xmlparams);
        $res = $WxObj->FromXml($xml);
        if ($res['return_code'] == 'SUCCESS') {
            if ($res['result_code'] == 'SUCCESS') {
                //修改订单状态 退款订单编号
                $updata = [
                    'out_refund_no' => $out_refund_no,
                    'status' => 3,
                    'return_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ];
                Db::name('order')
                    ->where('order_no', $data['order_no'])
                    ->where('user_id', $data['user_id'])
                    ->update($updata);

                echo $this->show(config('code.success'), '退款成功', ['code' => 200]);
                die;
            }
            //未支付
            $result = Db::name('order')
                ->where('order_no', $data['order_no'])
                ->where('user_id', $data['user_id'])
                ->update(['status' => 4]);
            echo $this->show(config('code.error'), $res['err_code_des'], ['code' => 203]);
            die;
        } else {
            $result = Db::name('order')
                ->where('order_no', $data['order_no'])
                ->where('user_id', $data['user_id'])
                ->update(['status' => 4]);
            $msgData = [
                'user_id' => $data['user_id'],
                'message' => '订单退款失败,如有疑问请联系平台客服',
            ];
            $this->savePushMsg($msgData);
            echo $this->show(config('code.error'), '退款失败,请前往商户后台核实订单状态', ['code' => 203]);
            die;
        }
    }
}