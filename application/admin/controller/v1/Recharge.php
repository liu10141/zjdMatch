<?php


namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class Recharge extends Base
{
    /**
     * 订单列表
     */
    public function getRechargeOrderList()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('recharge');
        $total = Db::name('recharge');
        $min = Db::name('recharge');
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
     * 导出充值订单
     */
    public function exportRechargeOrder()
    {
        $params = input('post.');
        $result = Db::name('recharge');
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
        //订单类型
        if (!empty($params['type'])) {
            $type = (int)$params['type'];
            $result->where('type', $type);
        }
        $ids = $result
            ->order('id', 'desc')
            ->column('id');
        if (empty($ids)) {
            $data = [];
            echo $this->show(config('code.success'), '导出充值列表', $data);
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
        echo $this->show(config('code.success'), '导出充值列表', $resultDatas);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除订单信息
     */
    public function delRechargeOrder()
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
     * 编辑订单状态
     */
    public function setRechargeOrderRemark()
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
        Db::name('recharge')
            ->where('id', $params['id'])
            ->update($upData);
        echo show(config('code.success'), '备注成功', ['code' => '202']);
        die;
    }

}