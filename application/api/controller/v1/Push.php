<?php


namespace app\admin\controller\v1;


use app\common\controller\Wxtools;
use think\Cache;
use think\Controller;

class Push extends Controller
{
    protected $TEMP_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';

    /**
     * 微信模板消息发送
     * @param $openid 接收用户的openid
     * return 发送结果
     *
     */
    public function send($obj, $token)
    {
        $url = $this->TEMP_URL . $token;
        $params =
            [
                'touser' => $obj["openid"],
                'template_id' => $obj["template_id"],//模板ID
                'url' => $obj["url"], //点击详情后的URL可以动态定义
                'data' => [
                    'first' => ['value' => $obj["first"], 'color' => '#173177'],
                    'keyword1' => ['value' => $obj["keyword1"], 'color' => '#FF0000'],
                    'keyword2' => ['value' => $obj["keyword2"], 'color' => '#13CE66'],
                    'keyword3' => ['value' => $obj["keyword3"], 'color' => '#3385ff'],
                    'remark' => ['value' => $obj["remark"], 'color' => '#FD572B']
                ]
            ];
        $json = json_encode($params, JSON_UNESCAPED_UNICODE);
        return $this->curlPost($url, $json);
    }

    /**
     * 获取分享的签名
     * 传入URL返回签名
     * jsapi_ticket 可以获取多次不是唯一的有效期是7200
     */
    public function SignPackage()
    {
        $params = input('post.');
        $url = $params['url'];
        $jsapiTicket = $this->getJsApiTicket();
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
//        $signature = substr($string, 0, strlen($string) - 1);
        $signature = sha1($string);
        $appid = config('wxconfig.appid');//
        $signPackage = array(
            "appId" => $appid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string,
            "jsapi_ticket" => $jsapiTicket
        );
        echo show(config('code.success'), '分享方法', $signPackage);
        die;
    }

    /**
     * @return mixed
     * 获取分享的js权限
     */
    private function getJsApiTicket()
    {
        $jsapi_ticket = Cache::get('jsapi_ticket');
        if (empty($jsapi_ticket)) {
            $accessToken = $this->getGzhAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode(curlGet($url), true);
            if ($res) {
                $ticket = isset($res['ticket']) ? $res['ticket'] : '';
                if (empty($ticket)) {
                    return false;
                }
                Cache::set('jsapi_ticket', $ticket, 7000);
            }
        } else {
            $ticket = $jsapi_ticket;
        }
        return $ticket;
    }


    /**
     * 通过CURL发送数据
     * @param $url 请求的URL地址
     * @param $data 发送的数据
     * return 请求结果
     */
    protected function curlPost($url, $data)
    {
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = FALSE; //是否返回响应头信息
        $params[CURLOPT_SSL_VERIFYPEER] = false;
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_POSTFIELDS] = $data;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }


    /**
     *获取公众号全局token
     */
    public function getGzhAccessToken()
    {
        $wxObj = new Wxtools();
        $AccessToken = $wxObj->getGzhAccessToken();
        return $AccessToken;
    }

    /**
     * @param $url
     * @param $v
     * @return bool|mixed|string
     * 请求数据
     */
    public function get_url($url, $v)
    {//http api接口json数据
        $html = file_get_contents($url);
        if ($v == "json") {
            return json_decode($html, true);
        } else {
            return $html;
        }
    }

}