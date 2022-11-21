<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2019/8/26
 * Time: 17:26
 */

namespace app\common\behavior;

use think\Exception;
use think\Response;

class CronRun
{
    public function run(&$dispatch)
    {
//        $host_name = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "*";
        header("Access-Control-Allow-Origin:*");
//        $allow_origin = array(
//            'bid.greatorange.cn',
//            '192.168.1.116:9527',
//        );
//        if (in_array($host_name, $allow_origin)) {
//            return;
//        }
        $headers = [
            "Access-Control-Allow-Origin" => '*',
            "Access-Control-Allow-Credentials" => 'true',
            "Access-Control-Allow-Methods" => 'GET, POST, OPTIONS, DELETE',
            "Access-Control-Allow-Headers" => "X-Token,x-token,x-uid,x-token-check,x-requested-with,content-type,Host,token,app-type,access-user-token,sign"
        ];
        if ($dispatch instanceof Response) {
            $dispatch->header($headers);
        } else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $dispatch['type'] = 'response';
            $response = new Response('', 200, $headers);
            $dispatch['response'] = $response;
        }
    }
}