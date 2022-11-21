<?php


namespace app\admin\controller\v1;


use app\common\model\UserModel;
use think\Db;
use think\Validate;

class User extends Base
{
    /**
     * 获取用户列表
     * 分页有延迟
     */
    public function getUserList()
    {
        $params = input('post.');
        $validate = new Validate([
            'page' => 'require',
            'pageSize' => 'require',
        ], [
            'page.require' => '请传入页面数',
            'pageSize.require' => '页面条数',
        ]);

        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $UserModelObj = new UserModel();
        $resultData = $UserModelObj->getUserList($params);
        echo $this->show(config('code.success'), '用户列表', $resultData);
        unset($params, $resultData);
        die;
    }

    /**
     * 设置用户状态
     */
    public function setUserStatus()
    {
        $params = input('post.');
        $validate = new Validate([
            'status' => 'require',
            'id' => 'require',
        ], [
            'status.require' => '用户状态',
            'id.require' => '用户id',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $res = Db::name('user')
            ->where('id', $params['id'])
            ->update(['status' => $params['status']]);
        if ($res) {
            echo $this->show(config('code.success'), '状态修改成功', []);
            unset($params);
            die;
        }
        echo $this->show(config('code.error'), '状态修改失败', []);
        unset($params);
        die;
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改用户信息
     */
    public function editUser()
    {
        $params = input('post.');
        $validate = new Validate([
            'balance' => 'require',
            'coupon_balance' => 'require',
            'mobile' => 'require',
            'name' => 'require',
            'vip_time_out' => 'require',
            'user_id' => 'require',
        ], [
            'balance.require' => '请输入余额',
            'coupon_balance.require' => '请输入积分',
            'mobile.require' => '请输入联系方式',
            'name.require' => '请输入客户姓名',
            'user_id.require' => '用户id',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $shop_id = 0;
        if ($params['is_admin'] == 1) {
            $shop_id = 25;
        }
        $data = [
            'balance' => $params['balance'],
            'coupon_balance' => $params['coupon_balance'],
            'mobile' => $params['mobile'],
            'name' => $params['name'],
            'shop_id' => $shop_id,
        ];

        Db::name('user')
            ->where('id', $params['user_id'])
            ->update(['vip_time_out' => strtotime($params['vip_time_out']), 'is_admin' => $params['is_admin']]);
        $res = Db::name('userinfo')
            ->where('user_id', $params['user_id'])
            ->update($data);
        if ($res) {
            echo $this->show(config('code.success'), '状态修改成功', []);
            unset($params);
            die;
        }
        echo $this->show(config('code.error'), '信息未变更', []);
        unset($params);
        die;
    }

}