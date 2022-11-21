<?php


namespace app\admin\controller\v1;

use app\common\controller\Push;
use app\common\controller\Qiniuyun;
use app\common\controller\Wxtools;
use think\Controller;
use think\Db;

class Index extends Controller
{

    /**
     *上传图片到七牛云
     */
    public function upImage()
    {
        $params = [
            'prefix' => config('qiniu.prefix'),
            'bucket' => config('qiniu.bucket'),
        ];
        $image = $_FILES['file']['tmp_name'];
        $bucket = $params['bucket'];
        $toolsObj = new Qiniuyun();
        $rand = rand(1, 999);
        $now = date('YmdHis/');
        $filename = $_FILES['file']['name'];
        $name = ucfirst($params['prefix']) . '/' . $now . $rand . '/' . $filename;
        $baseImgurl = $toolsObj->qiniu_upfile($image, $bucket, $name);
        $url = config('qiniu.baseUrl') . $baseImgurl;
        $data = [
            'imgUrl' => $url,
            'baseImg' => $baseImgurl,
        ];
        echo show(config('code.success'), '文件地址', $data);
        die;

    }

    /**
     *删除列表
     */
    public function delImage()
    {
        $toolsObj = new Qiniuyun();
        $imgList = Db::name('make_card')
            ->where('img', '<>', '')
            ->page(2, 100)->column('img');
        foreach ($imgList as $value) {
            $baseImgurl = $toolsObj->qiniu_delfile($value, 'nuochen');
            var_dump($baseImgurl) . '<br/>';
        }
    }

    /**
     * 取消无效订单
     * 1 服务中  2 待支付 3 已结束
     */
    public function queueSycOrder()
    {
        $canceOrder = Db::name('order_log')
            ->where('status', 1)
            ->field('id,end_time')
            ->select();
        $canceIds = [];
        foreach ($canceOrder as $item) {
            if (time() > $item['end_time']) {
                $canceIds[] = $item['id'];
            }
        }
        if (!empty($canceIds)) {
            Db::name('order_log')
                ->whereIn('id', $canceIds)
                ->update(['status' => 3]);
        }
        return true;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 修改进行中的订单
     */
    public function SycIngOrder()
    {

        $useOrder = Db::name('order')
            ->where('status', 1)
            ->field('id,user_id,status,order_no')
            ->select();
        if (empty($useOrder)) {
            return;
        }
        $order_nos = [];
        foreach ($useOrder as $value) {
            $order_no = Db::name('order_log')
                ->where('status', 1)
                ->where('order_no', $value['order_no'])
                ->column('order_no');
            if (empty($order_no)) {
                $order_nos[] = $value['order_no'];
            }
        }
        if (!empty($order_nos)) {
            Db::name('order')
                ->where('status', 1)
                ->whereIn('order_no', $order_nos)
                ->update(['status' => 5]);
        }
        return true;

    }

    /**
     * @return bool|void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     *
     */
    public function SycUserCoupon()
    {
        $useCoupon = Db::name('user_coupon')
            ->where('status', 2)
            ->field('id,user_id,time_out')
            ->select();
        if (empty($useCoupon)) {
            return;
        }
        $useCouponIds = [];
        foreach ($useCoupon as $value) {
            if ($value['time_out'] < time()) {
                $useCouponIds[] = $value['id'];
            }
        }
        if (!empty($useCouponIds)) {
            Db::name('user_coupon')
                ->where('status', 2)
                ->whereIn('id', $useCouponIds)
                ->update(['status' => 3]);
        }
        return true;

    }

    /**
     *推送模板消息
     * 用户消息
     */
    public function pushUserStartMsg($userids)
    {
        $tokenObj = new Wxtools();
        $token = $tokenObj->getGzhAccessToken();
        $userinfo = Db::name('userinfo')
            ->whereIn('user_id', $userids)
            ->field('nickname,gh_openid')
            ->select();
        if (empty($userinfo)) {
            return true;
        }
        $pushObj = new Push();
        foreach ($userinfo as $user) {
            $pushdata = [
                'template_id' => 'UMV30ITMQ8yfJwfYv_kSkEeIuZ1rFIYsZ1DMyPlPFjc',
                'url' => '',
                'openid' => $user['gh_openid'],//
                'first' => '尊敬的【' . $user['nickname'] . '】贵宾 您好！ 您预约的舞蹈室还有五分钟就要开始啦，请您及时前往练习哦！',
                'keyword1' => '订单将于大约五分钟后开始进行,请您及时确认以免错过哦',
                'keyword2' => date('Y-m-d H:i:s'),
                'keyword3' => '',
                'remark' => '壹间舞蹈室感谢您每一次的选择,如有疑问请联系客服处理',
            ];
            $res = $pushObj->send($pushdata, $token);
        }
        return true;
    }

    /**
     * @param $userids
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *即将结束通知
     */
    public function pushUserEndMsg($userids)
    {
        $tokenObj = new Wxtools();
        $token = $tokenObj->getGzhAccessToken();
        $userinfo = Db::name('userinfo')
            ->whereIn('user_id', $userids)
            ->field('nickname,gh_openid')
            ->select();
        if (empty($userinfo)) {
            return true;
        }
        $pushObj = new Push();
        foreach ($userinfo as $user) {
            $pushdata = [
                'template_id' => 'wPFdtye7rodW04sItAqlXIY4vwx-ZBrm7SS9AncqLF4',
                'url' => '',
                'openid' => $user['gh_openid'],//
                'first' => '尊敬的【' . $user['nickname'] . '】贵宾 您好！ 您预约的舞蹈室还有五分钟就要结束啦，如需继续使用请联系客服！',
                'keyword1' => '使用进度提醒',
                'keyword2' => '进行中',
                'keyword3' => '五分钟之后结束',
                'remark' => '感谢您每一次的选择,如有疑问请联系客服处理',
            ];
            $res = $pushObj->send($pushdata, $token);
        }
        return true;
    }

    /**
     * @param array $item
     * @param int $pid
     * @return array
     * 递归分类
     */
    public function getTree($item = array(), $pid = 0)
    {
        $data = array();
        foreach ($item as $key => $val) {
            if ($val['parentId'] == $pid) {
                $val['children'] = $this->getTree($item, $val['id']);
                unset($val['pid']);
                if (empty($val['children']) && $pid != 0) {
//                    unset($val['list']);
                }
                $data[] = $val;
            }
        }
        return $data;
    }
}