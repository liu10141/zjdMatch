<?php
/**
 * Created by PhpStorm.
 * User: MC
 * Date: 2021/10/10
 * Time: 11:11
 */

namespace app\admin\controller\v1;


use think\Db;
use think\Validate;

class Banner extends Base
{
    /**
     * TODO　设置banner图片
     */
    public function addBanner()
    {
        $params = input('post.');

        $validate = new Validate([
            'img_url' => 'require',
            'status' => 'require',
        ], [
            'img_url.require' => '轮播图不能为空',
            'status.require' => '轮播图不能为空',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError());
            die;
        }
        $data = [
            'img_url' => $params['img_url'],
            'skip_url' => $params['skip_url'],
            'status' => $params['status'],
            'create_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('banner')->insert($data);
        echo $this->show(config('code.success'), '添加成功', []);
        die;
    }

    /**
     * TODO　获取banner图片
     */
    public function getBanner()
    {
        $params = input('post.');
        $validate = new Validate([
            'pageSize' => 'require|max:3',
            'page' => 'require',
        ], [
            'pageSize.require' => '请传入条数',
            'page.require' => '请传入页数',
        ]);
//        0无效果，1url路径，2富文本
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $pageSize = (int)$params['pageSize'];
        $result = Db::name('banner');
        $total = Db::name('banner');
        $total = $total->count('*');
        $pageNum = ceil($total / $pageSize);
        $offset = $params['page'] - 1;
        $resultData = $result->field('update_time', true)
            ->order('id', 'desc')
            ->limit($offset * $pageSize, $pageSize)
            ->select();
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = (int)$pageSize;
        $paheInfo['total'] = $total;
        $resdata['pageInfo'] = $paheInfo;
        $resdata['result'] = $resultData;
        echo $this->show(config('code.success'), 'banner列表', $resdata);
        unset($resultData, $regions, $id);
        die;
    }

    /**
     * TODO 修改banner
     */
    public function editBanner()
    {
        $params = input('post.');

        $validate = new Validate([
            'id' => 'require',
            'img_url' => 'require',
            'status' => 'require',
        ], [
            'id.require' => '轮播图编号',
            'img_url.require' => 'banner图片不能为空',
            'status.require' => '状态',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError());
            die;
        }
        $data = [
            'img_url' => $params['img_url'],
            'status' => $params['status'],
        ];
        Db::name('banner')
            ->where('id', $params['id'])
            ->update($data);
        echo $this->show(config('code.success'), '修改成功', []);
        die;
    }

    /**
     * TODO 修改banner状态
     */
    public function editBannerStatus()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'status' => 'require',
        ], [
            'id.require' => '轮播图编号',
            'status.require' => '状态',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError());
            die;
        }
        $data = [
            'status' => $params['status'],
        ];
        Db::name('banner')
            ->where('id', $params['id'])
            ->update($data);
        echo $this->show(config('code.success'), '状态修改成功', []);
        die;
    }

    /**
     * TODO 删除状态
     */
    public function delBanner()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
        ], [
            'id.require' => '轮播图编号',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError());
            die;
        }
        Db::name('banner')
            ->where('id', $params['id'])
            ->delete();
        echo $this->show(config('code.success'), '状态修改成功', []);
        die;
    }
}