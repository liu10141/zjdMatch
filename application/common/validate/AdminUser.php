<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 17/7/27
 * Time: 下午5:25
 */
namespace app\common\validate;

use think\Validate;
class AdminUser extends Validate {

    protected $rule = [
        'password' => 'require|max:20',
        'phone'=>'require|number|length:11',
        'code'=>'number|length:4',

    ];
}