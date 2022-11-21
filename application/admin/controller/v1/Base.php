<?php

namespace app\admin\controller\v1;

use app\admin\controller\Common;
use think\Db;

/**
 * 后台基础类库
 * Class Base 1
 * @package app\admin\controller\v1
 */
class Base extends Common
{
    protected $shop_id = '';
    protected $menu_ids = '';
    protected $name = '';

    /**
     * 初始化的方法
     */
    public function _initialize()
    {
        // 判定用户是否登录
        $isLogin = $this->isLogin();
        if (!$isLogin) {
            echo $this->show(config('code.error'), '登录失败', ['code' => 202]);
            die;
        }
//        $isAuth = $this->isAuth();
//        if (!$isAuth) {
//            echo $this->show(config('code.error'), '暂无操作权限');
//            die;
//        }
        return true;
    }

    /**
     * 判定是否登录
     * @return bool
     */
    public function isLogin()
    {
        $headers = request()->header();
        $token = isset($headers['token']) ? $headers['token'] : '';
        if (empty($token)) {
            return false;
        }
        //根据token获取用户信息返回给客户端
        $user_info = Db::name('admin')
            ->where('token', $token)
            ->where('status', 1)
            ->field('time_out,shop_id,permission_id')
            ->find();
        if (empty($user_info)) {
            return false;
        }
        if ($user_info['time_out'] < time()) {
            echo $this->show(config('code.time'), '验证身份过期,请重新登录', ['data' => 'error']);
            die;
        }
        $this->shop_id = $user_info['shop_id'];
        //如果查询成功则继续执行代码
        return true;
    }


    /**
     *
     */
    public function upImgStatus($imgName)
    {
        Db::name('image_url')
            ->where('img_link', $imgName)
            ->update('status', 1);
        return true;
    }

    /**
     * @return bool
     * 判断是否有权限登录
     */
    public function isAuth()
    {
        $url = request()->url();
        $url = str_replace('/admin/v1', '', $url);
        $headers = request()->header();
        $permission_id = isset($headers['permissionid']) ? $headers['permissionid'] : '';
//
        $authinfo = Db::name('permissions')
            ->where('id', (int)$permission_id)
            ->field('permission,is_super,name')
            ->find();
        if ($authinfo['is_super'] == 1) {
            return true;
        }
        if (empty($authinfo['permission'])) {
            return false;
        }
        $permissionIds = json_decode($authinfo['permission'], true);
        $permissionInfo = Db::name('permission_actions')
            ->whereIn('id', $permissionIds)
            ->field('uri,menu_id')->select();
//        halt($permissionInfo);
        $urls = [];
        $menu_ids = [];
        $urls[] = '/getUserMenus';
        foreach ($permissionInfo as $value) {
            $urls[] = $value['uri'];
            $menu_ids[] = $value['menu_id'];
        }
        $this->menu_ids = $menu_ids;
        $this->name = $authinfo['name'];
//        halt($this->name);
        if (!in_array($url, $urls)) {
            return false;
        }
        return true;
    }
}
