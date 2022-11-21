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

class News extends Base
{
    /**
     * 添加活动
     */
    public function addNews()
    {
        $params = input('post.');
        $validate = new Validate([
            'title' => 'require',
            'cover' => 'require',
            'desc' => 'require',
            'content' => 'require',
        ], [
            'titile.require' => '活动标题',
            'cover.require' => '活动封面',
            'desc.require' => '活动简述',
            'content.require' => '活动内容不能为空',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError());
            die;
        }
        $data = [
            'title' => $params['title'],
            'cover' => $params['cover'],
            'desc' => $params['desc'],
            'type' => $params['type'],
            'content' => $params['content'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('news')->insert($data);
        echo $this->show(config('code.success'), '添加成功', []);
        die;
    }

    /**
     * TODO　获取News图片
     */
    public function getNews()
    {
        $params = input('post.');
        $validate = new Validate([
            'pageSize' => 'require|max:3',
            'page' => 'require',
        ], [
            'pageSize.require' => '请填写条数',
            'page.require' => '请填写页数',
        ]);
//        0无效果，1url路径，2富文本
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError(), []);
            die;
        }
        $pageSize = (int)$params['pageSize'];
        $result = Db::name('news');
        $total = Db::name('news');
        //活动状态
        if (!empty($params['status'])) {
            $result->where('status', $params['status']);
            $total->where('status', $params['status']);
        }

        //文章类型
        if (!empty($params['type'])) {
            $result->where('type', $params['type']);
            $total->where('type', $params['type']);
        }
//        创建时间00
        if (!empty($params['querydate'])) {
            $start_time = empty($params['querydate']) ? date('Y-m-d') : $params['querydate'][0];
            $end_time = empty($params['querydate']) ? date('Y-m-d') . ' 23:59:59' : $params['querydate'][1] . ' 23:59:59';
            $result->whereTime('create_time', 'between', [$start_time, $end_time]);
            $total->whereTime('create_time', 'between', [$start_time, $end_time]);
        }
        $total = $total->count('*');
        $pageNum = ceil($total / $pageSize);
        $offset = $params['page'] - 1;
        $resultData = $result->field('update_time', true)
            ->order('id', 'desc')
            ->limit($offset * $pageSize, $pageSize)
            ->select();
        foreach ($resultData as &$value) {
            $value['cover'] = config('qiniu.baseUrl') . $value['cover'];
        }
        $paheInfo['page'] = $pageNum;
        $paheInfo['pageSize'] = (int)$pageSize;
        $paheInfo['total'] = $total;
        $resdata['pageInfo'] = $paheInfo;
        $resdata['result'] = $resultData;
        echo $this->show(config('code.success'), '活动列表', $resdata);
        unset($resultData, $regions, $id);
        die;
    }

    /**
     * TODO 修改News
     * 0
     */
    public function editNews()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
            'title' => 'require',
            'cover' => 'require',
            'desc' => 'require',
            'content' => 'require',
        ], [
            'id.require' => '编号',
            'title.require' => '请填写标题',
            'cover.require' => '请上传封面',
            'desc.require' => '请输入简述',
            'content.require' => '请填写活动内容',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError());
            die;
        }
        $baseUrl = config('qiniu.baseUrl');
        $data = [
            'title' => $params['title'],
            'cover' => str_replace($baseUrl, '', $params['cover']),
            'desc' => $params['desc'],
            'content' => $params['content'],
            'status' => $params['status'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('news')
            ->where('id', $params['id'])
            ->update($data);
        echo $this->show(config('code.success'), '修改成功', []);
        die;
    }

    /**
     * TODO 修改News状态
     */
    public function editNewsStatus()
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
        Db::name('news')
            ->where('id', $params['id'])
            ->update($data);
        echo $this->show(config('code.success'), '状态修改成功', []);
        die;
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除活动
     */
    public function delNews()
    {
        $params = input('post.');
        $validate = new Validate([
            'id' => 'require',
        ], [
            'id.require' => '动态编号',
        ]);
        if (!$validate->check($params)) {
            echo $this->show(config('code.error'), $validate->getError());
            die;
        }

        Db::name('news')
            ->where('id', $params['id'])
            ->delete();
        echo $this->show(config('code.success'), '成功', []);
        die;
    }
}