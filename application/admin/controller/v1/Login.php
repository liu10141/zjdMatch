<?php

namespace app\admin\controller\v1;

use app\admin\controller\Common;
use app\common\lib\IAuth;
use think\Db;
use think\Validate;

class Login extends Common
{
    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 后台管理员登录
     */
    public function login()
    {
        $params = input('post.');
        $validate = new Validate([
            'user' => 'require',
            'pass' => 'require'
        ], [
            'user.require' => '用户名不能为空',
            'pass.require' => '密码不能为空',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $data = [
            'user' => $params['user'],
            'pass' => IAuth::setPassword($params['pass'])
        ];
        $admin = Db::name('admin')
            ->where($data)
            ->field('status,permission_id,token')
            ->find();
        if (empty($admin)) {
            echo $this->show(config('code.error'), '账号或密码输入错误', '');
            die;
        }
        $token = IAuth::setAppLoginToken($params['user']);
        $token = strtoupper(md5($token));
        $data = [
            'token' => $token,
            'time_out' => strtotime("+" . config('app.login_time_out_day') . " days")
        ];
        Db::name('admin')
            ->where('token', $admin['token'])
            ->update($data);
        $respone = [
            'token' => $token,
            'name' => $params['user'],
            'permissionid' => $admin['permission_id'],
        ];
        echo $this->show(config('code.success'), '登录成功', $respone);
        die;
    }

    /**
     * 退出登录的逻辑
     * 1、清空token
     */
    public function logout()
    {
        $headers = request()->header();
        $token = $headers['token'];
        AdminModel::where('token', $token)->update(['token' => '']);
        echo $this->show(config('code.success'), '退出成功', ['token' => '']);
        die;
    }

}

