<?php


namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class VipOrder extends Base
{
    /**
     * 订单列表000
     */
    public function getVipOrderList()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('vip_order');
        $total = Db::name('vip_order');
        $min = Db::name('vip_order');
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
            if (preg_match("/^1[345789]{1}\d*$/", $userinfo)) {
                $userIds = Db::name('userinfo')
                    ->where('mobile', 'like', $userinfo . '%')
                    ->column('user_id');
                if (empty($userIds)) {
                    echo $this->show(config('code.success'), '暂无数据', []);
                    die;
                }
                $result->whereIn('user_id', $userIds);
                $total->whereIn('user_id', $userIds);
                $min->whereIn('user_id', $userIds);
            } else {
                $userIds = Db::name('userinfo')
                    ->where('name', 'like', $userinfo . '%')
                    ->column('user_id');
                if (empty($userIds)) {
                    echo $this->show(config('code.success'), '暂无数据', []);
                    die;
                }
                $result->whereIn('user_id', $userIds);
                $total->whereIn('user_id', $userIds);
                $min->whereIn('user_id', $userIds);
            }
        }
//        支付状态
        if (!empty($params['status'])) {
            $status = (int)$params['status'];
            $result->where('status', $status);
            $total->where('status', $status);
            $min->where('status', $status);
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
            echo $this->show(config('code.success'), '订单列表', $data);
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
            foreach ($userinfos as $userinfo) {
                if ($resultData['user_id'] == $userinfo['user_id']) {
                    $resultData['mobile'] = $userinfo['mobile'];
                    $resultData['name'] = $userinfo['name'];
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
        echo $this->show(config('code.success'), '预约列表', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 导出会员订单
     */
    public function exportVipOrder()
    {
        $params = input('post.');
        $result = Db::name('vip_order');
//        创建时间
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
        }
        //支付类型 1 小程序支付 2 余额支付 3其他方式支付
        if (!empty($params['pay_type'])) {
            $result->where('pay_type', $params['pay_type']);
        }
//        支付状态
        if (!empty($params['status'])) {
            $status = (int)$params['status'];
            $result->where('status', $status);
        }
        $ids = $result
            ->order('id', 'desc')
            ->column('id');
        if (empty($ids)) {
            $data = [];
            echo $this->show(config('code.success'), '导出会员订单', $data);
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
            ->field('user_id,headimgurl,name,mobile')
            ->select();
        foreach ($resultDatas as &$resultData) {
            $resultData['name'] = '';
            $resultData['mobile'] = '';
            foreach ($userinfos as $userinfo) {
                if ($resultData['user_id'] == $userinfo['user_id']) {
                    $resultData['mobile'] = $userinfo['mobile'];
                    $resultData['name'] = $userinfo['name'];
                    $resultData['headimgurl'] = $userinfo['headimgurl'];
                }
            }
        }
        echo $this->show(config('code.success'), '导出会员订单', $resultDatas);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除订单信息
     */
    public function delVipOrder()
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
        Db::name('vip_order')
            ->where('id', $params['id'])
            ->delete();
        echo $this->show(config('code.success'), '删除成功', '');
        unset($upData, $params);
        die;


    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 会员退回
     * 修改会员到期时间
     * 修改用户余额 退回用户账户
     */
    public function returnVipOrder()
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
        $vipOrderInfo = Db::name('vip_order')
            ->where('id', $params['id'])
            ->field('user_id,price,order_no,pay_type')
            ->find();
        if ($vipOrderInfo['pay_type'] != 2) {
            echo $this->show(config('code.error'), '暂不支持兑换会员退款', []);
            unset($vipOrderInfo, $params, $validate);
            die;
        }
        $returnOrderData = [
            'out_refund_no' => 'QSR' . get_orn(),
            'status' => 3,
            'return_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('vip_order')
            ->where('id', $params['id'])
            ->update($returnOrderData);
        $vip_time_out = Db::name('user')
            ->where('id', $vipOrderInfo['user_id'])
            ->value('vip_time_out');
        $surplusTime = $vip_time_out - 365 * 86400;
        if ($surplusTime > 0) {
            $vip_time_out = $surplusTime;
        } else {
            $vip_time_out = time();
        }
        Db::name('user')
            ->where('id', $vipOrderInfo['user_id'])
            ->update(['vip_time_out' => $vip_time_out]);
        $userinfo = Db::name('userinfo')
            ->where('user_id', $vipOrderInfo['user_id'])
            ->field('balance,coupon_balance')
            ->find();
//      之前的天数续费
        $updata = [
            'balance' => $userinfo['balance'] + $vipOrderInfo['price'],
        ];
        Db::name('userinfo')
            ->where('user_id', $vipOrderInfo['user_id'])
            ->update($updata);
        echo $this->show(config('code.success'), '会员退款成功', []);
        unset($upData, $params);
        die;


    }

    /**
     * 编辑订单状态
     */
    public function setVipOrderRemark()
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
        Db::name('vip_order')
            ->where('id', $params['id'])
            ->update($upData);
        echo show(config('code.success'), '跟进成功', ['code' => '202']);
        die;
    }
}