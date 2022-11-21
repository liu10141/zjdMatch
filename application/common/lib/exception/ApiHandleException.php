<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 17/8/6
 * Time: 上午2:45
 */
namespace app\common\lib\exception;
use think\exception\Handle;
use Exception;
class ApiHandleException extends  Handle {

    /**
     * http 状态码
     * @var int
     */
    public $httpCode = 500;

     public function render(\Exception $e){
        if(config('app_debug')){
                        //如果开启debug则正常报错
            return parent::render($e);
        }else{
                        //404页面  自行定义
            header("Location:".url('home/index/errorpage'));
        }
    }
}