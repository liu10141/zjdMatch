<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 17/8/6
 * Time: 上午2:57
 */
namespace app\common\lib\exception;
use think\Exception;

class ApiException extends Exception 
{
    public $message = '';
    public $httpCode = 500;
    public $code = 0;
    /**
     * @param string $message 提示信息
     * @param int $httpCodez 状态码
     * @param int $code 提示吗
     */
    public function __construct($message = '', $httpCode = 0, $code = 0) 
    {
        $this->httpCode = $httpCode;
        $this->message = $message;
        $this->code = $code;
    }
}