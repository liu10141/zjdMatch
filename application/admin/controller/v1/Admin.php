<?php


namespace app\admin\controller\v1;


use app\common\lib\IAuth;
use app\common\model\AdminModel;
use think\Validate;

class Admin extends Base
{
    /**
     * 设置管理员密码 返回加密后的密码
     * 临时使用 在没有添加管理员的情况下
     * 0
     */
    public function setPass()
    {
        $pass = input('post.');
        $validate = new Validate([
            'pass' => 'require',
        ], [
            'pass.require' => '密码不能为空',
        ]);
        if (!$validate->check($pass)) {
            echo $this->show(config('code.error'), $validate->getError());
            die;
        }
        $headers = request()->header();
        $token = $headers['token'];
        $set_pass1 = IAuth::setPassword($pass['pass']);
        AdminModel::where('token', $token)->update(['pass' => $set_pass1]);
        echo show(config('code.success'), '密码重置成功', ['code' => 200]);
        die;
    }

    /**
     * 添加管理员
     * @return mixed
     */
    public function add()
    {
        $data = input('post.');

        $validate = new Validate([
            'user' => 'require',
            'pass' => 'require',
        ], [
            'user.require' => '用户名不能为空',
            'pass.require' => '密码不能为空',
        ]);
        if (!$validate->check($data)) {
            echo show(config('code.error'), $validate->getError());
            die;
        }

        $admin = AdminModel::where(['user' => $data['user']])->find();
        if ($admin) {
            echo show(config('code.error'), '该管理员账号已存在', ['code' => 202]);
            die;
        }
        $admin = new AdminModel();
        $admin->name = $data['user'];
        $admin->password = IAuth::setPassword($data['pass']);
        $admin->save();
        echo show(config('code.success'), '管理员添加成功', ['code' => 200]);
        die;
    }
}