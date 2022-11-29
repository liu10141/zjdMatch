<?php


namespace app\api\controller\v1;

use app\common\controller\Baidu;
use app\common\controller\Qiniuyun;
use app\common\lib\Aes;
use think\Controller;
use think\Db;

class Index extends Controller
{
    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取假的用户token
     */
    public function getToken()
    {
        $token = 'd879d7901d0a5682b2fcb5012443c8dc18876c68';
        $user_id = 103;
        $openid = 'oRjFh5Fwh-cFhyCPGN2IeQFamvFg';
        $obj = new Aes();
        $loginToken = $obj->encrypt($token . "||" . $user_id . '||' . $openid);
        echo $loginToken;
        die;
    }


    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取洗车店列表
     * 循环判断出当前位置距离门店的距离
     */
    public function getMatch()
    {

        if (empty($_GET['lat']) && empty($_GET['lng'])) {
            $shopList = Db::name('shop')
                ->where('status', 1)
                ->field('create_time,update_time,sort,status,desc', true)
                ->select();
            echo show(config('code.success'), '门店列表', $shopList);
            unset($shopList);
            die;
        }
        $shopList = Db::name('shop')
            ->where('status', 1)
            ->field('create_time,update_time,sort,status,desc', true)
            ->select();
        foreach ($shopList as &$v) {
            $v['distance'] = 0;
            $v['distance'] = $this->get_distance($_GET['lat'], $_GET['lng'], $v['lat'], $v['lng']);
            if (!empty($v['distance'])) {
                $v['distance'] = ceil($v['distance'] / 1000);
            }
        }
        $shopList = $this->arr_sort($shopList, 'distance');
        echo show(config('code.success'), '门店列表', $shopList);
        unset($shopList);
        die;

    }

    /**
     * 根据经纬度计算 经纬度
     * @param $lat1 当前位置 lat
     * @param $lon1 当前位置 lng
     * @param $lat2 门店地址经纬度
     * @param $lon2
     * @return float
     */
    public function get_distance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371393; //地球平均半径,单位米
        $dlat = deg2rad($lat2 - $lat1);//角度转化为弧度
        $dlon = deg2rad($lon2 - $lon1);
        $a = pow(sin($dlat / 2), 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * pow(sin($dlon / 2), 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d = $R * $c;
        return round($d);
    }

    /**
     * @param $arr
     * @param $key
     * @param int $order
     * @return bool
     * 二维数组 按二维指定列排序
     * $arr  要排序的二维数组
     * $key  排序依据的列
     * $order  升序|降序  默认升序
     */
    public function arr_sort($arr, $key, $order = SORT_ASC)
    {
        $key_arr = array_column($arr, $key);
        if (empty($key_arr)) {
            return false;
        }
        array_multisort($key_arr, $order, $arr);
        return $arr;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取轮播图
     */
    public function getBanner()
    {
        $BannerList = Db::name('banner')
            ->where('status', 1)
            ->field('create_time,update_time,status,sort,type', true)
            ->select();
        echo show(config('code.success'), '轮播图列表', $BannerList);
        unset($BannerList);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取活动列表
     * 1 活动 2 新闻动态
     */
    public function getActivity()
    {
        $ActivityList = Db::name('news')
            ->where('status', 1)
            ->where('type', 1)
            ->field('create_time,update_time,sort,status,content', true)
            ->select();
        foreach ($ActivityList as &$value) {
            $value['cover'] = config('qiniu.baseUrl') . $value['cover'];
        }
        echo show(config('code.success'), '活动轮播', $ActivityList);
        unset($ActivityList);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 企业动态
     */
    public function getNews()
    {
        $ActivityList = Db::name('news')
            ->where('status', 1)
            ->where('type', 2)
            ->field('create_time,update_time,sort,status,content', true)
            ->select();
        foreach ($ActivityList as &$value) {
            $value['cover'] = config('qiniu.baseUrl') . $value['cover'];
        }
        echo show(config('code.success'), '活动轮播', $ActivityList);
        unset($ActivityList);
        die;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取活动详情
     */
    public function getActivityDetails()
    {
        $Activity = Db::name('news')
            ->where('status', 1)
            ->where('id', $_GET['id'])
            ->field('create_time,update_time,sort,status', true)
            ->find();
        $Activity['cover'] = config('qiniu.baseUrl') . $Activity['cover'];
        echo show(config('code.success'), '活动详情', $Activity);
        unset($ActivityList);
        die;
    }

    /**
     *上传图片到七牛云0
     *七牛云地址 空间 前缀已经 成为配置文件
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
        $imgurl = $toolsObj->qiniu_upfile($image, $bucket, $name);
        $imgLink = config('qiniu.baseUrl') . $imgurl;
        echo show(config('code.success'), '文件地址', ['imgUrl' => $imgurl, 'imgLink' => $imgLink]);
        die;
    }

    /**
     * 上传车辆信息
     */
    public function upCarImage()
    {
        $params = [
            'prefix' => config('qiniu.prefix'),
            'bucket' => config('qiniu.bucket'),
        ];
        $image = $_FILES['file']['tmp_name'];
        $baiduObj = new Baidu();
        $carinfo = $baiduObj->car_ocr($image);
        $bucket = $params['bucket'];
        $toolsObj = new Qiniuyun();
        $rand = rand(1, 999);
        $now = date('YmdHis/');
        $filename = $_FILES['file']['name'];
        $name = ucfirst($params['prefix']) . '/' . $now . $rand . '/' . $filename;
        $imgurl = $toolsObj->qiniu_upfile($image, $bucket, $name);
        $imgLink = config('qiniu.baseUrl') . $imgurl;

        $respose = [
            'imgUrl' => $imgurl,
            'imgLink' => $imgLink,
            'car_no' => $carinfo['car_no'],
        ];
        echo show(config('code.success'), '文件地址', $respose);
        die;
    }

}