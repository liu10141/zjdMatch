<?php


namespace app\common\model;


use think\Db;

class UserModel
{

    /**0
     * 获取用户数据
     */
    public function getUserList($reqdata)
    {
        $pageSize = $reqdata['pageSize'];
        $result = Db::name('user')
            ->alias('u')
            ->join('userinfo i', 'u.id=i.user_id');
        $total = Db::name('user')
            ->alias('u')
            ->join('userinfo i', 'u.id=i.user_id');
        $min = Db::name('user')
            ->alias('u')
            ->join('userinfo i', 'u.id=i.user_id');
//        查询时间
        if (!empty($reqdata['querydate'])) {
            $end_time = empty($reqdata['querydate']) ? date('Y-m-d') . ' 23:59:59' : $reqdata['querydate'][1] . ' 23:59:59';
            $start_time = empty($reqdata['querydate']) ? '2010-01-01' : $reqdata['querydate'][0];
            $result->whereTime('u.create_time', 'between', [$start_time, $end_time]);
            $total->whereTime('u.create_time', 'between', [$start_time, $end_time]);
            $min->whereTime('u.create_time', 'between', [$start_time, $end_time]);
        }
//        用户信息
        if (!empty($reqdata['userinfo'])) {
            $result->where('i.name', $reqdata['userinfo']);
            $total->where('i.name', $reqdata['userinfo']);
            $min->where('i.name', $reqdata['userinfo']);
        }
//        是否会员
        if (!empty($reqdata['is_vip'])) {
            $tiaojian = $reqdata['is_vip'] == 1 ? '>' : '<';
            $result->where('u.vip_time_out', $tiaojian, time());
            $total->where('u.vip_time_out', $tiaojian, time());
            $min->where('u.vip_time_out', $tiaojian, time());
        }
//        用户状态
        if (!empty($reqdata['status'])) {
            $status = $reqdata['status'];
            $result->where('u.status', $status);
            $total->where('u.status', $status);
            $min->where('u.status', $status);
        }
        if (!empty($reqdata['is_admin'])) {
            $is_admin = $reqdata['is_admin'];
            $result->where('u.is_admin', $is_admin);
            $total->where('u.is_admin', $is_admin);
            $min->where('u.is_admin', $is_admin);
        }
        $total = $total->count('*');
        $pageNum = ceil($total / $pageSize);
        $ids = $result
            ->order('u.id', 'desc')
            ->limit($pageSize * ($reqdata['page'] - 1), $pageSize)
            ->column('u.id');
        if (empty($ids)) {
            $paheInfo['page'] = $pageNum;
            $paheInfo['pageSize'] = $pageSize;
            $paheInfo['total'] = $total;
            $resdata['pageInfo'] = $paheInfo;
            $resdata['result'] = [];
            return $resdata;
        }
        $resultData = $min->field('u.id,u.is_admin,u.status,u.create_time,u.vip_time_out,i.nickname,i.name,i.mobile,i.headimgurl,i.balance,i.consume_count,i.build_num,i.coupon_balance,i.vip_no,i.user_id')
            ->whereIn('u.id', $ids)
            ->order('u.id', 'desc')
            ->select();
        foreach ($resultData as &$val) {
            $val['headimgurl'] = str_replace('/132', '/0', $val['headimgurl']);
        }
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = (int)$pageSize;
        $paheInfo['total'] = $total;
        $resdata['pageInfo'] = $paheInfo;
        $resdata['result'] = $resultData;
        unset($resultData, $regions, $id);
        return $resdata;
    }


}