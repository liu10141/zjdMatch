<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
use think\Route;

Route::group('api/:ver', function () {
    ###############################天空之橙官网 案例展示图片##############################################
    //上传图片
    Route::post('upImage', 'api/:ver.index/upImage');
//    客流统计
    Route::post('shopCount', 'api/:ver.user/shopCount');
//    上传车牌信息
    Route::post('upCarImage', 'api/:ver.index/upCarImage');
//    微信绑定
    Route::post('makeOrder', 'api/:ver.order/makeOrder');
    //获取权限
    Route::post('getSignPackage', 'api/:ver.share/getSignPackage');
    //获取活动列表
    Route::get('getActivity', 'api/:ver.index/getActivity');

    Route::get('getNews', 'api/:ver.index/getNews');
//    活动详情页
    Route::get('getActivityDetails', 'api/:ver.index/getActivityDetails');
//    动态详情
    Route::get('getNewsDetails', 'api/:ver.index/getNewsDetails');
//    获取分类
    Route::get('getBanner', 'api/:ver.index/getBanner');

    Route::get('getShop', 'api/:ver.index/getShop');
//    房间详情
    Route::get('getShopDetails', 'api/:ver.index/getShopDetails');
//    会员列表
    Route::get('getVip', 'api/:ver.index/getVipPrice');
    //会员权益描述00
    Route::get('getVipDesc', 'api/:ver.index/getVipDesc');

//    获取token TODO 用于测试
    Route::get('getToken', 'api/:ver.index/getToken');

    ##########################################用户订单相关###########################################################
//  充值订单查询
    Route::post('queryRechargeOrder', 'api/:ver.order/queryRechargeOrder');
    //充值余额
    Route::post('rechargeOrder', 'api/:ver.order/rechargeOrder');
//    取消订单
    Route::post('cancelOrder', 'api/:ver.order/cancelOrder');
    //用户小程序支付会员订单
    Route::post('payVipOrder', 'api/:ver.order/payVipOrder');
//    查询会员订单支付情况
    Route::post('queryVipOrder', 'api/:ver.order/queryVipOrder');
//    生成用户消费订单
    Route::post('makeOrder', 'api/:ver.order/makeOrder');
//    使用抵扣券下单
    Route::post('makeOrderUseCoupon', 'api/:ver.order/makeOrderUseCoupon');
//  支付汽车消费项目金额
    Route::post('payCarOrder', 'api/:ver.order/payCarOrder');
//    查询汽车订单支付情况
    Route::post('queryCarOrder', 'api/:ver.order/queryCarOrder');
    //    获取进行中的订单
    Route::get('getUserOrder', 'api/:ver.user/getUserOrder');
//    获取用户充值记录
    Route::get('getUserRecharge', 'api/:ver.user/getUserRecharge');
    //半价下单
    Route::post('makeOrderUseDiscountCoupon', 'api/:ver.order/makeOrderUseDiscountCoupon');
    ##########################################用户信息##################################################################
//    修改个人信息
    Route::post('editUserInfo', 'api/:ver.user/editUserInfo');
//    添加车辆信息
    Route::post('addCar', 'api/:ver.user/addCar');

    Route::post('editCar', 'api/:ver.user/editCar');
    //小程序登录
    Route::post('wx_mini_login', 'api/:ver.login/wx_mini_login');
//    微信个人信息
    Route::get('getUserInfo', 'api/:ver.user/getUserinfo');
//    用户绑定的车辆
    Route::get('getUserCar', 'api/:ver.user/getUserCar');
//    兑换会员
    Route::post('exchangeVip', 'api/:ver.order/exchangeVip');
//    兑换抵扣券
    Route::post('exchangeCoupon', 'api/:ver.order/exchangeCoupon');
//    用户优惠券
    Route::get('getUserCoupon', 'api/:ver.user/getUserCoupon');
//    店长帮忙充值
    Route::post('shopRechargeOrder', 'api/:ver.order/shopRechargeOrder');

    ##########################################服务项目##################################################################
//    获取分类
    Route::get('getClass', 'api/:ver.Product/getClass');
//    获取产品列表
    Route::get('getProduct', 'api/:ver.Product/getProduct');
//    获取会员卡号
    Route::get('getVipNo', 'api/:ver.index/getVipNo');
//    微信回调
    Route::post('notify', 'api/:ver.notify/notify');


});

//http://120.26.72.248/
//http://ServerIP/api/IsConnect
//IsConnect
//校验二维码权限
Route::post('api/CheckCode', 'api/v1.index/CheckCode');
//xinxin
Route::post('api/IsConnect', 'api/v1.index/IsConnect');
//后台管理系统搭建完成
Route::group('admin/:ver', function () {
    //登录
    Route::post('login', 'admin/:ver.login/login');
//    取消无效订单
    Route::get('queueSycOrder', 'admin/:ver.index/queueSycOrder');
//    生成会员兑换码
    Route::post('createVipCode', 'admin/:ver.vip/createVipCode');
//    处理订单状态
    Route::get('SycIngOrder', 'admin/:ver.index/SycIngOrder');
//    上传文件
    Route::post('upImage', 'admin/:ver.index/upImage');
//    筛选项目列表
    Route::get('getSelectProject', 'admin/:ver.project/getSelectProject');
//    用户券码列表
    Route::post('getUserCouponList', 'admin/:ver.coupon/getUserCouponList');
    #######################################订单列表##############################
    //  会员订单列表
    Route::post('getVipOrderList', 'admin/:ver.vipOrder/getVipOrderList');
//    导出消费订单
    Route::post('exportSaleOrder', 'admin/:ver.Order/exportSaleOrder');
    Route::post('exportVipOrder', 'admin/:ver.vipOrder/exportVipOrder');
    Route::post('exportRechargeOrder', 'admin/:ver.recharge/exportRechargeOrder');
//    订单详情
    Route::post('getOrderDetial', 'admin/:ver.Order/getOrderDetial');
//    备注订单信息
    Route::post('setVipOrderRemark', 'admin/:ver.vip/setVipOrderRemark');
//   备注消费订单
    Route::post('setSaleOrderRemark', 'admin/:ver.sale/setSaleOrderRemark');
//    备注充值订单
    Route::post('setRechargeOrderRemark', 'admin/:ver.Recharge/setRechargeOrderRemark');
//    微信退款
    Route::post('wx_return_money', 'admin/:ver.order/wx_return_money');
//   获取充值订单记录
    Route::post('getRechargeOrderList', 'admin/:ver.recharge/getRechargeOrderList');
//    获取消费订单列表
    Route::post('getSaleOrderList', 'admin/:ver.sale/getSaleOrderList');
//    退款消费订单
    Route::post('returnSaleOrder', 'admin/:ver.sale/returnSaleOrder');
//   会员模板列表
    Route::post('getVipList', 'admin/:ver.vip/getVipList');
//    会员卡券
    Route::post('getVipCoupon', 'admin/:ver.vip/getVipCoupon');
//    修改会员信息
    Route::post('editVip', 'admin/:ver.vip/editVip');
//    退会员费用
    Route::post('returnVipOrder', 'admin/:ver.vipOrder/returnVipOrder');
    //修改权益
    Route::post('editVipDesc', 'admin/:ver.vip/editVipDesc');
//    会员权益
    Route::post('getVipDescList', 'admin/:ver.vip/getVipDesc');
    #########################################活动相关######################################################
    Route::post('getNews', 'admin/:ver.news/getNews');
//   添加活动
    Route::post('addNews', 'admin/:ver.news/addNews');
//    修改活动
    Route::post('editNews', 'admin/:ver.news/editNews');
//    修改活动状态
    Route::post('editNewsStatus', 'admin/:ver.news/editNewsStatus');
//    删除
    Route::post('delNews', 'admin/:ver.news/delNews');
    #########################################门店相关######################################################
    Route::post('getShopList', 'admin/:ver.shop/getShop');
//    修改门店
    Route::post('editShop', 'admin/:ver.shop/editShop');
//    删除门店
    Route::post('delShop', 'admin/:ver.shop/delShop');
    //添加门店
    Route::post('addShop', 'admin/:ver.shop/addShop');
//    设置门店状态
    Route::post('setShopStatus', 'admin/:ver.shop/setShopStatus');
    #########################################车辆相关相关######################################################
    Route::post('getCarList', 'admin/:ver.car/getCarList');
//    修改车辆信息
    Route::post('editCar', 'admin/:ver.car/editCar');
//  删除车辆
    Route::post('delCar', 'admin/:ver.car/delCar');
//    设置车辆状态
    Route::post('setCarStatus', 'admin/:ver.car/setCarStatus');

    #########################################项目相关######################################################
    Route::post('getProjectList', 'admin/:ver.project/getProjectList');
//    添加时间段
    Route::post('addProject', 'admin/:ver.project/addProject');
//    修改舞蹈室
    Route::post('editProject', 'admin/:ver.project/editProject');
//    删除舞蹈室
    Route::post('delProject', 'admin/:ver.project/delProject');
//    设置项目状态
    Route::post('setProjectStatus', 'admin/:ver.project/setProjectStatus');
//    获取项目分类
    Route::get('getProjectClass', 'admin/:ver.project/getProjectClass');
    #########################################优惠券相关######################################################
    Route::post('getCouponList', 'admin/:ver.coupon/getCouponList');
//    添加时间段
    Route::post('createCoupon', 'admin/:ver.coupon/createCoupon');
//    修改舞蹈室
    Route::get('getProjectClass', 'admin/:ver.project/getProjectClass');
//    #################################轮播图###############################################
    Route::post('getBannerList', 'admin/:ver.banner/getBanner');
//    添加首页轮播
    Route::post('addBanner', 'admin/:ver.banner/addBanner');
//    修改banner
    Route::post('editBanner', 'admin/:ver.banner/editBanner');
//    修改状态
    Route::post('editBannerStatus', 'admin/:ver.banner/editBannerStatus');
//    删除banner
    Route::post('delBanner', 'admin/:ver.banner/delBanner');
#########################################用户相关列表信息
    Route::post('userList', 'admin/:ver.user/getUserList');
//    修改用户信息
    Route::post('editUser', 'admin/:ver.user/editUser');
//   设置用户状态
    Route::post('setUserStatus', 'admin/:ver.user/setUserStatus');
//    修改密码
    Route::post('edit_user_pass', 'admin/:ver.admin/setPass');
});