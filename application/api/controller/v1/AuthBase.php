<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 17/8/5
 * Time: 下午4:37
 */

namespace app\api\controller\v1;


use app\common\lib\Aes;
use think\Db;

/**
 * 客户端auth登录权限基础类库
 * 这个判断用户是否登录
 * 1、每个接口(需要登录  个人中心 点赞 评论）都需要去集
 * 2、判定 access_user_token 是否合法
 * 3、用户信息  user
 * Class AuthBase
 * @package app\api\controller\00
 */
class AuthBase extends Common
{
    /**
     * 登录用户的基本信息
     * @var array
     */
    public $user = [];
    public $user_id = '';
    public $openid = '';
    public $vip_time_out = '';

    /**
     * 初始化
     */
    public function _initialize()
    {
        parent::_initialize();
        /**
         * 判断是否为空登录信息
         * 判断是否登录 其他控制器都继承authbase
         */
        //暂时去掉
        $isLogin = $this->isLogin();
        if (!$isLogin) {
            echo $this->show(config('code.time'), '您没有登录', ['data' => 'error']);
            die;
        }
    }

    /**
     * 判定是否登录
     * @return  boolen
     */
    public function isLogin()
    {
        if (empty($this->headers['token'])) {
            return false;
        }
        $obj = new Aes();
        //解密出来的
        $accessUserToken = $obj->decrypt($this->headers['token']);
        if (empty($accessUserToken)) {
            return false;
        }
        if (!preg_match('/||/', $accessUserToken)) {
            return false;
        }
        /*把accessUserToken 分割成数组*/
        list($token, $user_id) = explode("||", $accessUserToken);
        //根据token获取用户信息返回给客户端
        $userInfo = Db::name('user')
            ->where('token', $token)
            ->field('id,token_time_out,status,openid,vip_time_out')
            ->find();
        if (empty($userInfo)) {
            echo $this->show(config('code.time'), '用户信息不存在', ['data' => 'error']);
            die;
        }
        if ($userInfo['status'] != 1) {
            echo $this->show(config('code.error'), '账号已冻结,如有疑问请联系客服处理', ['data' => 'error']);
            die;
        }
        //判定时间是否过期0
        if (time() > $userInfo['token_time_out']) {
            echo $this->show(config('code.time'), '验证身份过期请重新登录', ['data' => 'error']);
            die;
        }
        $this->user_id = $user_id;
        $this->openid = $userInfo['openid'];
        $this->vip_time_out = $userInfo['vip_time_out'];
        return true;
    }

    /**
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 消费记录
     */
    protected function saleLog($data)
    {

        Db::name('sale_log')->insert($data);
        return true;
    }

    /**
     * 赠送卡券
     * $couponData=[];
     */
    public function saveCoupon($project_id, $project_name)
    {
        $coupon_code = strtoupper(sp_gm_get_gift_code());
        $CouponData = [
            'code' => $coupon_code,
            'project_name' => $project_name,
            'project_id' => $project_id,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('coupon_code')->insert($CouponData);
        $userCoupon = [
            'user_id' => $this->user_id,
            'coupon_code' => $coupon_code,
            'project_name' => $project_name,
            'project_id' => $project_id,
            'time_out' => time() + 86400 * 30,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        Db::name('user_coupon')->insert($userCoupon);
        return;

    }


}