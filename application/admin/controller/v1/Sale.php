<?php


namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class Sale extends Base
{
    /**
     * 消费订单列表
     */
    public function getSaleOrderList()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('order');
        $total = Db::name('order');
        $min = Db::name('order');
//        创建时间
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
            $total->whereTime('create_time', 'between', [$start_time, $end_time]);
            $min->whereTime('create_time', 'between', [$start_time, $end_time]);
        }
//        订单编号
        if (!empty($params['order_no'])) {
            $result->where('order_no', $params['order_no']);
            $total->where('order_no', $params['order_no']);
            $min->where('order_no', $params['order_no']);
        }
        //支付类型 1 小程序支付 2 余额支付 3其他方式支付
        if (!empty($params['pay_type'])) {
            $result->where('pay_type', $params['pay_type']);
            $total->where('pay_type', $params['pay_type']);
            $min->where('pay_type', $params['pay_type']);
        }
        $userinfo = $params['userinfo'];
        if (!empty($userinfo)) {
            if (preg_match("/^1[345789]{1}\d$/", $userinfo)) {
                $userIds = Db::name('userinfo')
                    ->where('mobile', 'like', $userinfo . '%')
                    ->column('user_id');
                if (!empty($userIds)) {
                    $result->whereIn('user_id', $userIds);
                    $total->whereIn('user_id', $userIds);
                    $min->whereIn('user_id', $userIds);
                }
            } else {
                $userIds = Db::name('userinfo')
                    ->where('name', 'like', $userinfo . '%')
                    ->column('user_id');
                if (!empty($userIds)) {
                    $result->whereIn('user_id', $userIds);
                    $total->whereIn('user_id', $userIds);
                    $min->whereIn('user_id', $userIds);
                }

            }
        }
//        支付状态
        if (!empty($params['status'])) {
            $status = (int)$params['status'];
            $result->where('status', $status);
            $total->where('status', $status);
            $min->where('status', $status);
        }
        //订单类型
        if (!empty($params['type'])) {
            $type = (int)$params['type'];
            $result->where('type', $type);
            $total->where('type', $type);
            $min->where('type', $type);
        }
        $total = $total->count('*');
        $pageNum = ceil($total / $pageSize);
        $ids = $result
            ->order('id', 'desc')
            ->limit($pageSize * ($params['page'] - 1), $pageSize)
            ->column('id');
        if (empty($ids)) {
            $paheInfo['page'] = $pageNum;
            $paheInfo['pageSize'] = $pageSize;
            $paheInfo['total'] = $total;
            $data['pageInfo'] = $paheInfo;
            $data['result'] = [];
            echo $this->show(config('code.success'), '消费订单', $data);
            die;
        }
        $resultDatas = $min->field('update_time', true)
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
            ->field('user_id,headimgurl,name,mobile')
            ->select();
        foreach ($resultDatas as &$resultData) {
            $resultData['name'] = '';
            $resultData['mobile'] = '';
            $resultData['all_price'] = $resultData['price'] + $resultData['discount_price'];
            foreach ($userinfos as $userinfo) {
                if ($resultData['user_id'] == $userinfo['user_id']) {
                    $resultData['mobile'] = $userinfo['mobile'];
                    $resultData['name'] = $userinfo['name'];
                    $resultData['headimgurl'] = $userinfo['headimgurl'];
                }
            }
        }
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = $pageSize;
        $paheInfo['total'] = $total;
        $data['pageInfo'] = $paheInfo;
        $data['result'] = $resultDatas;
        echo $this->show(config('code.success'), '消费订单', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除订单信息
     */
    public function delSaleOrder()
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 退款消费订单
     * 用户账户余额退回
     */
    public function returnSaleOrder()
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
        $orderInfo = Db::name('order')
            ->where('order_no', $params['order_no'])
            ->field('price,discount_price,pay_type,user_id')
            ->find();
        if ($orderInfo['pay_type'] != 2) {
            echo $this->show(config('code.error'), '该订单不支持退款', []);
            die;
        }
        $orderData = [
            'out_refund_no' => 'QSR' . get_orn(),
            'status' => 3,
            'return_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('order')
            ->where('order_no', $params['order_no'])
            ->update($orderData);
        Db::name('sale_log')
            ->where('order_no', $params['order_no'])
            ->update(['status' => 3]);
        $userinfo = Db::name('userinfo')
            ->where('user_id', $orderInfo['user_id'])
            ->field('balance,coupon_balance')
            ->find();
//      之前的天数续费
        $updata = [
            'balance' => $userinfo['balance'] + $orderInfo['price'],
            'coupon_balance' => $userinfo['coupon_balance'] + $orderInfo['discount_price'],
            'consume_count' => $userinfo['consume_count'] - 1,
        ];
        Db::name('userinfo')
            ->where('user_id', $orderInfo['user_id'])
            ->update($updata);
        echo $this->show(config('code.success'), '订单明细', []);
        unset($upData, $params);
        die;
    }

    /**
     * 编辑订单状态0
     */
    public function setSaleOrderRemark()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'remark' => 'require',
        ], [
            'id.require' => '订单id',
            'remark.require' => '填写备注信息',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $upData = [
            'remark' => $params['remark'],
        ];
        Db::name('order')
            ->where('id', $params['id'])
            ->update($upData);
        echo show(config('code.success'), '跟进成功', ['code' => '202']);
        die;
    }

}