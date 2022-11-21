<?php


namespace app\api\controller\v1;


use think\Db;
use think\Validate;

class User extends AuthBase
{
    /**
     * 获取用户信息
     * 配置充值00
     */
    public function getUserinfo()
    {
        $userinfo = Db::name('userinfo')
            ->where('user_id', $this->user_id)
            ->field('id,create_time,update_time,nickname,headimgurl,signature,gh_openid,remark', true)
            ->find();
        $user = Db::name('user')
            ->where('id', $this->user_id)
            ->field('vip_time_out,is_admin')->find();
        $userCoupon = Db::name('user_coupon')
            ->where('user_id', $this->user_id)
            ->where('status', 2)
            ->count('*');
        $userinfo['vip_time_out'] = $user['vip_time_out'];
        $userinfo['is_admin'] = $user['is_admin'];
        $userinfo['userCoupon'] = $userCoupon;
        $userinfo['is_show'] = true;
        $userinfo['qrcode'] = time() + 7200 . '-' . $this->user_id;
        echo $this->show(config('code.success'), '用户信息', $userinfo);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取订单记录
     */
    public function getUserOrder()
    {
        $orderInfo = Db::name('order')
            ->where('user_id', $this->user_id)
            ->where('status', $_GET['status'])
            ->order('id', 'desc')
            ->field('shop_id,num,order_no,car_no,status,pay_type,discount_price,price,num,projects,create_time')
            ->select();

        if (empty($orderInfo)) {
            echo $this->show(config('code.success'), '订单信息', []);
            die;
        }
        $shopInfo = Db::name('shop')
            ->field('id,name,cover')
            ->select();
        foreach ($orderInfo as &$order) {
            foreach ($shopInfo as $shop) {
                if ($order['shop_id'] == $shop['id']) {
                    $order['name'] = $shop['name'];
                    $order['cover'] = $shop['cover'];
                }
            }
        }
        echo $this->show(config('code.success'), '订单信息', $orderInfo);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户绑定车辆
     */
    public function getUserCar()
    {
        $carInfo = Db::name('user_car')
            ->where('user_id', $_GET['user_id'])
            ->where('status', 1)
            ->field('create_time,update_time,status', true)
            ->select();
        if (empty($carInfo)) {
            echo $this->show(config('code.success'), '车辆信息', []);
            die;
        }
        echo $this->show(config('code.success'), '车辆信息', $carInfo);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户优惠券
     */
    public function getUserCoupon()
    {
        if ($_GET['status'] == 2) {
            $couponInfo = Db::name('user_coupon')
                ->where('user_id', $this->user_id)
                ->where('status', $_GET['status'])
                ->order('time_out', 'asc')
                ->field('update_time', true)
                ->select();
        } else {
            $couponInfo = Db::name('user_coupon')
                ->where('user_id', $this->user_id)
                ->where('status', $_GET['status'])
                ->order('id', 'desc')
                ->field('update_time', true)
                ->select();
        }


        if (empty($couponInfo)) {
            echo $this->show(config('code.success'), '我的优惠券', []);
            die;
        }
        echo $this->show(config('code.success'), '我的优惠券', $couponInfo);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserRecharge()
    {
        $couponInfo = Db::name('recharge')
            ->where('user_id', $this->user_id)
            ->where('status', 1)
            ->field('update_time', true)
            ->select();
        if (empty($couponInfo)) {
            echo $this->show(config('code.success'), '我的充值明细', []);
            die;
        }
        echo $this->show(config('code.success'), '我的充值明细', $couponInfo);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 客流统计
     */
    public function shopCount()
    {
        $params = input('post.');
        $validate = new Validate([
            'shop_id' => 'require',
            'wait_car' => 'require',
            'wait_time' => 'require',
        ], [
            'shop_id.require' => '请输入门店编号',
            'wait_car.require' => '请评估等待车辆',
            'wait_time.require' => '请评估门店等待时间',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $data = [
            'wait_car' => $params['wait_car'],
            'wait_time' => $params['wait_time'],
        ];
        Db::name('shop')
            ->where('id', $params['shop_id'])
            ->update($data);
        echo $this->show(config('code.success'), '修改信息完成', []);
        die;
    }

    /**
     *修改用户信息
     */
    public function editUserInfo()
    {
        $params = input('post.');
        $validate = new Validate([
            'mobile' => 'require|max:11|/^1[3-9]{1}[0-9]{9}$/',
            'name' => 'require',
            'photo' => 'require',
        ], [
            'mobile.require' => '请输入联系方式',
            'mobile./^1[3-8]{1}[0-9]{9}$/' => '手机号格式不正确',
            'name.require' => '请传入您的姓名',
            'photo.require' => '请上传个人照片',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $user_id = Db::name('userinfo')
            ->where('mobile', $params['mobile'])
            ->value('user_id');
        if (!empty($user_id) && $user_id != $this->user_id) {
            echo $this->show(config('code.error'), '该手机号已被绑定', []);
            die;
        }
        $data = [
            'mobile' => $params['mobile'],
            'name' => $params['name'],
            'birthday' => $params['birthday'],
            'photo' => $params['photo'],
            'address' => $params['address'],
        ];
        $queueInfo = Db::name('userinfo')
            ->where('user_id', $this->user_id)
            ->update($data);
        echo $this->show(config('code.success'), '修改信息完成', $queueInfo);
        die;
    }

    /**
     * 绑定车辆信息
     */
    public function addCar()
    {
        $params = input('post.');
        $validate = new Validate([
            'car_no' => 'require|unique:user_car',
            'brand_name' => 'require',
        ], [
            'car_no.require' => '车牌号',
            'car_no.unique' => '车牌号已存在',
            'brand_name.require' => '车辆品牌',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
//        开启事务
        Db::startTrans();
        try {
            $carData = [
                'user_id' => $this->user_id,
                'car_no' => strtoupper($params['car_no']),
                'car_photo' => $params['car_photo'],
                'car_type' => $params['car_type'],
                'brand_name' => $params['brand_name'],
                'car_color' => $params['car_color'],
                'status' => 1,
                'vin_no' => $params['vin_no'],
                'create_time' => date('Y-m-d H:i:s'),
                'next_edit_time' => time(),
                'update_time' => date('Y-m-d H:i:s'),
            ];
            Db::name('user_car')->insert($carData);
            Db::name('userinfo')
                ->where('user_id', $this->user_id)
                ->setInc('build_num', 1);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            echo show(config('code.error'), '绑定失败', []);
            die;
        }
        echo show(config('code.success'), '绑定成功', ['code' => '202']);
        die;

    }

    /**
     * 修改车辆辆信息
     */
    public function editCar()
    {
        $params = input('post.');
        $validate = new Validate([
            'car_no' => 'require',
            'brand_name' => 'require',
            'car_photo' => 'require',
        ], [
            'car_no.require' => '车牌号',
            'car_photo.require' => '车牌照片',
            'brand_name.require' => '车辆品牌',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $carData = [
            'car_no' => strtoupper($params['car_no']),
            'car_photo' => $params['car_photo'],
            'car_type' => $params['car_type'],
            'brand_name' => $params['brand_name'],
            'car_color' => $params['car_color'],
            'status' => 1,
            'vin_no' => $params['vin_no'],
            'next_edit_time' => strtotime('+1 year'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('user_car')
            ->where('user_id', $this->user_id)
            ->where('id', $params['id'])
            ->update($carData);;
        echo show(config('code.success'), '修改绑定成功', ['code' => '202']);
        die;

    }

}