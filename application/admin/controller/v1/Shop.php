<?php


namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class Shop extends Base
{
    /**
     * TODO 获取地点列表
     */
    public function getShop()
    {
        $params = input('post.');
        $validate = new Validate([
            'pageSize' => 'require',
            'page' => 'require',
        ], [
            'pageSize.require' => 'banner图片不能为空',
            'page.require' => '跳转地址',
        ]);
//        0无效果，1url路径，2富文本
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $pageSize = (int)$params['pageSize'];
        $result = Db::name('shop');
        $total = Db::name('shop');
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
            $total->whereTime('create_time', 'between', [$start_time, $end_time]);
        }
        if (!empty($params['is_rent'])) {
            $result->where('is_rent', $params['is_rent']);
            $total = $total->where('is_rent', $params['is_rent']);
        }
//       是否启用
        if (!empty($params['status'])) {
            $result->where('status', $params['status']);
            $total = $total->where('status', $params['status']);
        }
//       舞蹈室名称
        if (!empty($params['name'])) {
            $result->where('name', 'like', '%' . $params['name'] . '%');
            $total = $total->where('name', '%' . $params['name'] . '%');
        }
//        地址
        if (!empty($params['address'])) {
            $result->where('address', 'like', $params['name'] . '%');
            $total = $total->where('address', 'like', $params['name'] . '%');
        }
        $total = $total->count('*');
        $pageNum = ceil($total / $pageSize);
        $offset = $params['page'] - 1;
        $resultData = $result->field('update_time,sort', true)
            ->order('id', 'desc')
            ->limit($offset * $pageSize, $pageSize)
            ->select();
//        foreach ($resultData as &$val) {
//            $val['cover'] = config('qiniu.baseUrl') . $val['cover'];
//        }
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = (int)$pageSize;
        $paheInfo['total'] = $total;
        $resdata['pageInfo'] = $paheInfo;
        $resdata['result'] = $resultData;
        echo $this->show(config('code.success'), '舞蹈室列表', $resdata);
        unset($resultData, $regions, $id);
        die;
    }

    /**
     * 添加门店
     */
    public function addShop()
    {
        $params = input('post.');
        $validate = new Validate([
            'name' => 'require',
            'address' => 'require',
            'cover' => 'require',
            'is_rent' => 'require',
            'price' => 'require',
        ], [
            'name.require' => '请传入地点名称',
            'address.require' => '请上传地址',
            'cover.require' => '请上传舞蹈室封面',
            'is_rent.require' => '请选择是否出租舞蹈室',
            'price.require' => '请输入出租价格',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $data = [
            'name' => $params['name'],
            'address' => $params['address'],
            'cover' => $params['cover'],
            'is_rent' => $params['is_rent'],
            'price' => $params['price'],
            'mobile' => $params['mobile'],
            'lat' => $params['lat'],
            'lng' => $params['lng'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('shop')->insert($data);
        echo show(config('code.success'), '添加成功', []);
        die;
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     *  修改门店信息
     */
    public function editShop()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'name' => 'require',
            'address' => 'require',
            'cover' => 'require',
            'status' => 'require',
            'wait_time' => 'require',
            'wait_car' => 'require',
        ], [
            'id.require' => '请传入编号',
            'name.require' => '请传入地点名称',
            'address.require' => '请上传地址',
            'cover.require' => '请上传封面地址',
            'status.require' => '请选择所属分类',
            'wait_car.require' => '请输入等待人数',
            'wait_time.require' => '请输入等待时间',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $data = [
            'name' => $params['name'],
            'address' => $params['address'],
            'cover' => $params['cover'],
            'wait_time' => $params['wait_time'],
            'wait_car' => $params['wait_car'],
            'desc' => $params['desc'],
            'mobile' => $params['mobile'],
            'lat' => $params['lat'],
            'lng' => $params['lng'],
            'status' => $params['status'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('shop')
            ->where('id', $params['id'])
            ->update($data);
        echo show(config('code.success'), '修改成功', []);
        die;
    }

    /**
     * 删除
     */
    public function delShop()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
        ], [
            'id.require' => '请传入编号',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        Db::name('shop')
            ->where('id', $params['id'])
            ->delete();
        echo show(config('code.success'), '操作成功', []);
        die;
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 设置舞蹈室状态
     */
    public function setShopStatus()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'status' => 'require',
        ], [
            'id.require' => '请传入编号',
            'status.require' => '请选择状态',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $data = [
            'status' => $params['status'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('shop')
            ->where('id', $params['id'])
            ->update($data);
        echo show(config('code.success'), '状态修改成功', []);
        die;
    }
}