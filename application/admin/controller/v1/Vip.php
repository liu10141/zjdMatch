<?php
/**
 * Created by PhpStorm.
 * User: MC
 * Date: 2021/9/19
 * Time: 18:02
 */

namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class Vip extends Base
{
    /**
     * 会员模板
     */
    public function getVipList()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('vip');
        $total = Db::name('vip');
        $min = Db::name('vip');
//        会员名称
        if (!empty($params['name'])) {
            $result->where('name', $params['name']);
            $total->where('name', $params['name']);
            $min->where('name', $params['name']);
        }
//        状态
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
            $paheInfo['pageNum'] = $pageNum;
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
//        if (!empty($resultDatas)) {
//            foreach ($resultDatas as $v) {
//                $userIds[] = $v['user_id'];
//                $roomIds[] = $v['room_id'];
//            }
//        }
//        $roomIds = array_unique($roomIds);
//        $userIds = array_unique($userIds);
//        $roominfo = Db::name('room')
//            ->whereIn('id', $roomIds)
//            ->field('id,name')
//            ->select();
//        $userinfos = Db::name('userinfo')
//            ->whereIn('user_id', $userIds)
//            ->field('user_id,nickname,mobile')
//            ->select();
//        foreach ($resultDatas as &$resultData) {
//            $resultData['name'] = '';
//            $resultData['mobile'] = '';
//            foreach ($userinfos as $userinfo) {
//                if ($resultData['user_id'] == $userinfo['user_id']) {
//                    $resultData['name'] = $userinfo['name'];
//                    $resultData['mobile'] = desensitize($userinfo['mobile'], 3, 4);;
//                }
//            }
//            $resultData['room_name'] = '';
//            foreach ($roominfo as $rooms) {
//                if ($resultData['room_id'] == $rooms['id']) {
//                    $resultData['room_name'] = $rooms['name'];
//                }
//            }
//        }
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = $pageSize;
        $paheInfo['total'] = $total;
        $data['pageInfo'] = $paheInfo;
        $data['result'] = $resultDatas;
        echo $this->show(config('code.success'), '会员模板', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * 修改会员信息
     */
    public function editVip()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'name' => 'require',
            'const_price' => 'require',
            'end_time' => 'require',
            'price' => 'require',
        ], [
            'id.require' => '请传入编号',
            'name.require' => '请传入地点名称',
            'const_price.require' => '请输入原价',
            'end_time.require' => '请选择活动结束时间',
            'price.require' => '请输入会员价格',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $data = [
            'name' => $params['name'],
            'const_price' => $params['const_price'],
            'end_time' => $params['end_time'],
            'price' => $params['price'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('vip')->where('id', $params['id'])->update($data);
        echo $this->show(config('code.success'), '修改成功', '');
        unset($data);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 设置订单备注
     */
    public function setVipOrderRemark()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'remark' => 'require',
        ], [
            'id.require' => '订单id',
            'remark.require' => '填写订单备注信息',
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
        echo $this->show(config('code.success'), '订单备注', '');
        unset($upData, $params);
        die;


    }

    /**
     * 生成会员信息
     */
    public function createVipCode()
    {
        $params = input('post.');
        $validate = new Validate([
            'num' => 'require',
            'valid_day' => 'require',
        ], [
            'num.require' => '请输入生成数量',
            'valid_day.require' => '请输入会员天数',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        if ($params['num'] > 2000) {
            echo $this->show(config('code.error'), '单次不能超过两千张', []);
            die;
        }
        $data = [];
        for ($i = 0; $i < $params['num']; $i++) {
            $data[$i]['code'] = strtoupper(sp_gm_get_gift_vip_code());
            $data[$i]['valid_day'] = $params['valid_day'];
            $data[$i]['create_time'] = date('Y-m-d H:i:s');
            $data[$i]['update_time'] = date('Y-m-d H:i:s');
        }
        Db::name('vip_code')->insertAll($data);
        echo $this->show(config('code.success'), '生成成功', '');
        unset($data);
        die;
    }

    /**
     * 获取会员卡券列表
     */
    public function getVipCoupon()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('vip_code');
        $total = Db::name('vip_code');
        $min = Db::name('vip_code');
//        会员名称
        if (!empty($params['code'])) {
            $result->where('code', $params['code']);
            $total->where('code', $params['code']);
            $min->where('code', $params['code']);
        }
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
//        状态
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
        echo $this->show(config('code.success'), '会员卡券', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * 修改会员权益描述
     */
    public function editVipDesc()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'img_url' => 'require',
            'desc' => 'require',
        ], [
            'id.require' => '请传入编号',
            'img_url.require' => '请上传权益图片',
            'desc.require' => '请输入会员权益介绍',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $data = [
            'img_url' => $params['img_url'],
            'desc' => $params['desc'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('vip_desc')->where('id', $params['id'])->update($data);
        echo $this->show(config('code.success'), '修改成功', '');
        unset($data);
        die;
    }

    /**
     * 会员权益介绍
     */
    public function getVipDesc()
    {
        $result = Db::name('vip_desc')
            ->where('status', 1)->find();
        echo $this->show(config('code.success'), '会员权益', $result);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

}