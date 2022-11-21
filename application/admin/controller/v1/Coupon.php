<?php


namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class Coupon extends Base
{
    /**
     * 优惠券列表
     */
    public function getCouponList()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('coupon_code');
        $total = Db::name('coupon_code');
        $min = Db::name('coupon_code');
//        项目ID
        if (!empty($params['project_id'])) {
            $result->where('project_id', $params['project_id']);
            $total->where('project_id', $params['project_id']);
            $min->where('project_id', $params['project_id']);
        }
        if (!empty($params['code'])) {
            $result->where('code', $params['code']);
            $total->where('code', $params['code']);
            $min->where('code', $params['code']);
        }
//        优惠券状态
        if (!empty($params['status'])) {
            $result->where('status', $params['status']);
            $total->where('status', $params['status']);
            $min->where('status', $params['status']);
        }
//        创建时间
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
            $total->whereTime('create_time', 'between', [$start_time, $end_time]);
            $min->whereTime('create_time', 'between', [$start_time, $end_time]);
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
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = $pageSize;
        $paheInfo['total'] = $total;
        $data['pageInfo'] = $paheInfo;
        $data['result'] = $resultDatas;
        echo $this->show(config('code.success'), '用户列表', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 创建优惠券
     */
    public function createCoupon()
    {
        $params = input('post.');
        $validate = new Validate([
            'num' => 'require',
            'project_id' => 'require',
            'coupon_type' => 'require',
        ], [
            'num.require' => '请输入生成数量',
            'project_id.require' => '请选择所属项目',
            'coupon_type.require' => '请选择卡券类型',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        if ($params['num'] > 2000) {
            echo $this->show(config('code.error'), '单次不能超过两千张', []);
            die;
        }
        $projectName = Db::name('project')
            ->where('id', $params['project_id'])
            ->value('project_name');
        $data = [];
        for ($i = 0; $i < $params['num']; $i++) {
            $data[$i]['code'] = strtoupper(sp_gm_get_gift_code());
            $data[$i]['project_name'] = $projectName;
            $data[$i]['project_id'] = $params['project_id'];
            $data[$i]['coupon_type'] = $params['coupon_type'];
            $data[$i]['create_time'] = date('Y-m-d H:i:s');
            $data[$i]['update_time'] = date('Y-m-d H:i:s');
        }
        Db::name('coupon_code')->insertAll($data);
        echo $this->show(config('code.success'), '生成成功', '');
        unset($data);
        die;
    }

    /**
     * 获取用户优惠券列表
     */
    public function getUserCouponList()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('user_coupon');
        $total = Db::name('user_coupon');
        $min = Db::name('user_coupon');
        $userinfo = $params['userinfo'];
        if (!empty($userinfo)) {
            if (preg_match("/^1[345789]{1}\d{9}$/", $userinfo)) {
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
                    ->where('nickname', 'like', $userinfo . '%')
                    ->column('user_id');
                if (!empty($userIds)) {
                    $result->whereIn('user_id', $userIds);
                    $total->whereIn('user_id', $userIds);
                    $min->whereIn('user_id', $userIds);
                }

            }
        }
//        券吗
        if (!empty($params['coupon_code'])) {
            $result->where('coupon_code', $params['coupon_code']);
            $total->where('coupon_code', $params['coupon_code']);
            $min->where('coupon_code', $params['coupon_code']);
        }
//        项目优惠券
        if (!empty($params['project_id'])) {
            $result->where('project_id', $params['project_id']);
            $total->where('project_id', $params['project_id']);
            $min->where('project_id', $params['project_id']);
        }
//        创建时间
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
            $total->whereTime('create_time', 'between', [$start_time, $end_time]);
            $min->whereTime('create_time', 'between', [$start_time, $end_time]);
        }
//        获取方式
        if (!empty($params['type'])) {
            $result->where('type', $params['type']);
            $total->where('type', $params['type']);
            $min->where('type', $params['type']);
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
            echo $this->show(config('code.success'), '用户列表', $data);
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
            ->field('user_id,nickname,headimgurl,mobile,name')
            ->select();
        foreach ($resultDatas as &$resultData) {
            $resultData['mobile'] = '';
            foreach ($userinfos as $userinfo) {
                if ($resultData['user_id'] == $userinfo['user_id']) {
                    $resultData['name'] = $userinfo['name'];
                    $resultData['headimgurl'] = $userinfo['headimgurl'];
                    $resultData['mobile'] = $userinfo['mobile'];
                }
            }
        }
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = $pageSize;
        $paheInfo['total'] = $total;
        $data['pageInfo'] = $paheInfo;
        $data['result'] = $resultDatas;
        echo $this->show(config('code.success'), '用户优惠券列表', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }
}
