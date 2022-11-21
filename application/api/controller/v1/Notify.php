<?php


namespace app\api\controller\v1;


use think\Controller;

class Notify extends Controller
{
    /**
     *微信回调0
     */
    public function notify()
    {
        $testxml = file_get_contents("php://input");
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，
        file_put_contents('orderLog.txt', json_encode($result) . PHP_EOL, FILE_APPEND);
        echo '<xml>
                      <return_code><![CDATA[SUCCESS]]></return_code>
                         <return_msg><![CDATA[OK]]></return_msg>
                        </xml>';
        die;
    }

}
