<?php


namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class Project extends Base
{
    /**
     * 订单列表
     */
    public function getProjectList()
    {
        $params = input('post.');
        $pageSize = $params['pageSize'];
        $result = Db::name('project');
        $total = Db::name('project');
        $min = Db::name('project');
//        创建时间
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
            $total->whereTime('create_time', 'between', [$start_time, $end_time]);
            $min->whereTime('create_time', 'between', [$start_time, $end_time]);
        }
//        项目名称
        if (!empty($params['project_name'])) {
            $result->where('project_name', $params['project_name']);
            $total->where('project_name', $params['project_name']);
            $min->where('project_name', $params['project_name']);
        }
        //项目分类
        if (!empty($params['class_id'])) {
            $result->where('class_id', $params['class_id']);
            $total->where('class_id', $params['class_id']);
            $min->where('class_id', $params['class_id']);
        }
        //车辆分类
        if (!empty($params['car_type'])) {
            $result->where('car_type', $params['car_type']);
            $total->where('car_type', $params['car_type']);
            $min->where('car_type', $params['car_type']);
        }
//        项目状态
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
            echo $this->show(config('code.success'), '消费项目列表', $data);
            die;
        }
        $resultDatas = $min->field('update_time', true)
            ->whereIn('id', $ids)
            ->order('id', 'desc')
            ->select();

        $classinfos = Db::name('class')
            ->field('cat_name,id')
            ->select();
        foreach ($resultDatas as &$resultData) {
            $resultData['cat_name'] = '';
            foreach ($classinfos as $class) {
                if ($resultData['class_id'] == $class['id']) {
                    $resultData['cat_name'] = $class['cat_name'];
                }
            }
        }
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = $pageSize;
        $paheInfo['total'] = $total;
        $data['pageInfo'] = $paheInfo;
        $data['result'] = $resultDatas;
        echo $this->show(config('code.success'), '消费项目列表', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取项目分类
     */
    public function getProjectClass()
    {
        $data = Db::name('class')
            ->where('status', 1)
            ->field('id,cat_name')
            ->select();
        echo $this->show(config('code.success'), '消费分类列表', $data);
        unset($result, $total, $paheInfo, $params, $start_time, $end_time, $data, $resultData);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除订单信息
     */
    public function delProject()
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
        Db::name('project')
            ->where('id', $params['id'])
            ->delete();
        echo $this->show(config('code.success'), '删除成功', '');
        unset($upData, $params);
        die;


    }

    /**
     * 编辑订单状态0
     */
    public function setProjectStatus()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'status' => 'require',
        ], [
            'id.require' => '订单id',
            'status.require' => '请输入项目状态',
        ]);
        if (!$validate->check($params)) {
            echo show(config('code.error'), $validate->getError(), ['code' => '202']);
            die;
        }
        $upData = [
            'status' => $params['status'],
        ];
        Db::name('project')
            ->where('id', $params['id'])
            ->update($upData);
        echo show(config('code.success'), '修改成功', ['code' => '202']);
        die;
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 获取项目筛选
     */
    public function getSelectProject()
    {

        $project = Db::name('project')
            ->where('status', 1)
            ->field('id,project_name')->select();
        echo show(config('code.success'), '项目列表', $project);
        die;
    }

    /**
     * 添加项目
     */
    public function addProject()
    {
        $params = input('post.');
        $validate = new Validate([
            'project_name' => 'require',
            'vip_price' => 'require',
            'server_time' => 'require',
            'price' => 'require',
            'class_id' => 'require',
            'car_type' => 'require',
        ], [
            'project_name.require' => '请传入会员名称',
            'vip_price.require' => '请输入会员价格',
            'price.require' => '请输入项目价格',
            'class_id.require' => '请选择项目所属分类',
            'car_type.require' => '请选择车辆类型',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $data = [
            'project_name' => $params['project_name'],
            'vip_price' => $params['vip_price'],
//            'cover' => $params['cover'],
            'price' => $params['price'],
            'server_time' => $params['server_time'],
            'class_id' => $params['class_id'],
            'car_type' => $params['car_type'],
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('project')->insert($data);
        echo show(config('code.success'), '添加成功', []);
        die;
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     *  修改项目信息
     */
    public function editProject()
    {
        $params = input('post.');
        $validate = new Validate([
            'project_name' => 'require',
            'id' => 'require',
            'vip_price' => 'require',
            'server_time' => 'require',
            'price' => 'require',
            'class_id' => 'require',
            'car_type' => 'require',
        ], [
            'id.require' => '请传入项目编号',
            'project_name.require' => '请传入会员名称',
            'vip_price.require' => '请输入会员价格',
            'price.require' => '请输入项目价格',
            'class_id.require' => '请选择项目所属分类',
            'car_type.require' => '请选择项目所属分类',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $data = [
            'project_name' => $params['project_name'],
            'vip_price' => $params['vip_price'],
            'server_time' => $params['server_time'],
            'price' => $params['price'],
            'class_id' => $params['class_id'],
            'car_type' => $params['car_type'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('project')
            ->where('id', $params['id'])
            ->update($data);
        echo show(config('code.success'), '修改成功', []);
        die;
    }
}