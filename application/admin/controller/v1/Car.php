<?php


namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class Car extends Base
{
    /**
     * 订单列表
     */
    public function getCarList()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('user_car');
        $total = Db::name('user_car');
        $min = Db::name('user_car');
        $userinfo = $params['userinfo'];
        if (!empty($userinfo)) {
            if (preg_match("/^1[345789]{1}\d$/", $userinfo)) {
                $userIds = Db::name('userinfo')
                    ->where('mobile', $userinfo)
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
//        车辆类型
        if (!empty($params['car_type'])) {
            $result->where('car_type', $params['car_type']);
            $total->where('car_type', $params['car_type']);
            $min->where('car_type', $params['car_type']);
        }
//        创建时间
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
            $total->whereTime('create_time', 'between', [$start_time, $end_time]);
            $min->whereTime('create_time', 'between', [$start_time, $end_time]);
        }
//        品牌类型
        if (!empty($params['brand_name'])) {
            $result->where('brand_name', $params['brand_name']);
            $total->where('brand_name', $params['brand_name']);
            $min->where('brand_name', $params['brand_name']);
        }
//        车牌号
        if (!empty($params['car_no'])) {
            $result->where('car_no', $params['car_no']);
            $total->where('car_no', $params['car_no']);
            $min->where('car_no', $params['car_no']);
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
            echo $this->show(config('code.success'), '车辆列表', $data);
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
                    $resultData['user_name'] = $userinfo['name'];
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
        echo $this->show(config('code.success'), '车辆列表', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 设置订单备注
     */
    public function setCarStatus()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'user_id' => 'require',
            'status' => 'require',
        ], [
            'id.require' => '订单id',
            'user_id.require' => '用户id',
            'status.require' => '填写车辆状态',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $upData = [
            'status' => $params['status'],
        ];
        Db::name('user_car')
            ->where('id', $params['id'])
            ->update($upData);
        if ($params['status'] == 1) {
            Db::name('userinfo')
                ->where('user_id', $params['user_id'])
                ->setInc('build_num', 1);
        } else {
            Db::name('userinfo')
                ->where('user_id', $params['user_id'])
                ->setDec('build_num', 1);
        }
        echo $this->show(config('code.success'), '修改成功', '');
        unset($upData, $params);
        die;


    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除订单信息
     */
    public function delCar()
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
        Db::name('user_car')
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
     * 修改车辆信息
     */
    public function editCar()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'car_color' => 'require',
            'car_no' => 'require',
            'vin_no' => 'require',
            'brand_name' => 'require',
        ], [
            'id.require' => '订单id',
            'car_color.require' => '车辆颜色为',
            'car_no.require' => '车牌号',
            'brand_name.require' => '车辆品牌',
            'vin_no.require' => '车架号',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $data = [
            'car_color' => $params['car_color'],
            'car_no' => $params['car_no'],
            'vin_no' => $params['vin_no'],
            'brand_name' => $params['brand_name'],
            'car_type' => $params['car_type'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('user_car')
            ->where('id', $params['id'])
            ->update($data);
        echo $this->show(config('code.success'), '修改成功', []);
        unset($upData, $params);
        die;


    }


}