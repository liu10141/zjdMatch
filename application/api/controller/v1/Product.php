<?php


namespace app\api\controller\v1;


use think\Controller;
use think\Db;

class Product extends Controller
{
    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取服务项目分类
     */
    public function getClass()
    {
        $classList = Db::name('class')
            ->where('status', 1)
            ->field('id,cat_name')
            ->select();
        echo show(config('code.success'), '服务项目', $classList);
        unset($BuildFlimList);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取下拉项目列表
     * 筛选项目列表
     */
    public function getSelectProject()
    {
        $project = Db::name('project')
            ->where('status', 1)
            ->field('id,project_name')
            ->select();
        echo $this->show(config('code.success'), '项目列表', $project);
        unset($project);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取产品列表00
     */
    public function getProduct()
    {
        $projectList = Db::name('project')
            ->where('status', 1)
            ->where('class_id', $_GET['class_id'])
            ->where('car_type', $_GET['car_type'])
            ->field('id,project_name,vip_price,server_time,cover,price,is_num')
            ->select();
        echo show(config('code.success'), '服务项目', $projectList);
        unset($BuildFlimList);
        die;
    }

}