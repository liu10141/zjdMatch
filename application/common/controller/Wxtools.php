<?php


namespace app\common\controller;


use think\Cache;
use think\Controller;

class Wxtools extends Controller
{
    /**
     *设置签名
     */
    public function SetSign($datas)
    {
        ksort($datas);
        $string = $this->ToUrlParamss($datas);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . config('wxconfig.key');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        $datas['sign'] = $result;//签名
        $xml = $this->ToXml($datas);
        return $xml;
    }


    /**
     * 获取平台公钥
     */
    public function getPublickey()
    {
        $redisObj = new RedisTools();
        $serial_infos = $redisObj->get('serial_info');
        if (empty($serial_infos)) {
            $serial_info = $this->getcertificates();
            $serial_info = $serial_info['data'][0];
            $time = strtotime($serial_info['expire_time']);
            $serial_info = [
                'associated_data' => $serial_info['encrypt_certificate']['associated_data'],
                'nonce' => $serial_info['encrypt_certificate']['nonce'],
                'ciphertext' => $serial_info['encrypt_certificate']['ciphertext'],
            ];
            $serial_infos = json_encode($serial_info);
            $time = $time - time();
            $redisObj->set('serial_info', $serial_infos, $time);
        }
        $serial_infos = json_decode($serial_infos, true);
        $publicKey = $this->Decrypt($serial_infos['associated_data'], $serial_infos['nonce'], $serial_infos['ciphertext']);
        return $publicKey;

    }

    /**
     * @param 上传图片文件媒体资源
     */
    public function uploadFile($filename)
    {
        $url = 'https://api.mch.weixin.qq.com/v3/merchant/media/upload';
        $fi = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $fi->file($filename);
        $data['filename'] = '1.png';
        $meta['filename'] = '1.png';
        $meta['sha256'] = hash_file('sha256', $filename);
        $boundary = uniqid(); //分割符号
        $sign = $this->SetMiniRsaSign(json_encode($meta), $url);//$http_method要大写
        $header[] = 'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.108 Safari/537.36';
        $header[] = 'Accept:application/json';
        $header[] = 'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $sign;
        $header[] = 'Content-Type:multipart/form-data;boundary=' . $boundary;
        $boundaryStr = "--{$boundary}\r\n";
        $out = $boundaryStr;
        $out .= 'Content-Disposition: form-data; name="meta"' . "\r\n";
        $out .= 'Content-Type: application/json' . "\r\n";
        $out .= "\r\n";
        $out .= json_encode($meta) . "\r\n";
        $out .= $boundaryStr;
        $out .= 'Content-Disposition: form-data; name="file"; filename="' . $data['filename'] . '"' . "\r\n";
        $out .= 'Content-Type: ' . $mime_type . ';' . "\r\n";
        $out .= "\r\n";
        $out .= file_get_contents($filename) . "\r\n";
        $out .= "--{$boundary}--\r\n";
        $r = $this->doCurl($url, $out, $header);
        var_dump($r);
        die;
    }

    /**
     * @param $body
     * @return array
     * 设置服务商支付签名
     * 返回签名
     * 服务商总的商户id
     *  $url = 'https://wechatpay-api.gitbook.io/wechatpay-api-v3';
     */
    public function SetMiniRsaSign($body, $url)
    {

        $http_method = "POST";
        $timestamp = time();//时间戳
        $mch_private_key = $this->getPrivatekey();
        $merchant_id = config('wxconfig.sp_mchid');
        $nonce = $this->getNonceStr();
        $url_parts = parse_url($url);
        $serial_no = config('wxconfig.api_serial_no');//API证书序列号
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message =
            $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchant_id, $nonce, $timestamp, $serial_no, $sign);
        return $token;
    }

    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 输出xml字符
     **/
    public function ToXml($datas)
    {
        $xml = "<xml>";
        foreach ($datas as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     * [url=home.php?mod=space&uid=67594]@Return[/url] 返回已经拼接好的字符串
     */
    public function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParamss($datas)
    {
        $buff = "";
        foreach ($datas as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml 需要post的xml数据
     * @param string $url url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second url执行超时时间，默认30s
     */
    public function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        curl_close($ch);
        return $data;
    }

    /**
     * 将xml转为array
     * @param string $xml
     */
    public function FromXml($xml)
    {
        if (!$xml) {
            echo "xml数据异常！";
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /**
     * @param $url
     * @param $postfields
     * @return bool|string
     * 微信退款需要证书
     * TODO 微信证书
     */
    public function postStr($url, $postfields)
    {
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_SSL_VERIFYPEER] = false;//禁用证书校验
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        //以下是证书相关代码
        $sslCertPath = '/www/wwwroot/carshop/cert/apiclient_cert.pem';
        $sslKeyPath = '/www/wwwroot/carshop/cert/apiclient_key.pem';
        $params[CURLOPT_SSLCERTTYPE] = 'PEM';
        $params[CURLOPT_SSLCERT] = $sslCertPath;
        $params[CURLOPT_SSLKEYTYPE] = 'PEM';
        $params[CURLOPT_SSLKEY] = $sslKeyPath;
        $params[CURLOPT_POSTFIELDS] = $postfields;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }

    /**
     * 获取平台证书， 与商户证书不是一个内容
     * 需要GET请求
     * 获取证书有限期
     */
    public function getcertificates()
    {
        //生成V3请求 header认证信息
        $url = "https://api.mch.weixin.qq.com/v3/certificates";
        $header = $this->getAuthorization($url, 'GET', '');
//        halt($header);
        $result = curlGet($url, $header);
        return json_decode($result, true);

    }

    /**
     * V3加密平台证书
     * 加密信息
     */
    public function getEncrypt($str)
    {
        //$str是待加密字符串
        $public_key = $this->getPublickey(); //看情况使用证书， 个别接口证书 使用的是 平台证书而不是 api证书
        $encrypted = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new Exception('encrypt failed');
        }
        return $sign;
    }

    /**
     * @param $aesKey
     * @param $associatedData
     * @param $nonceStr
     * @param $ciphertext
     * 解密获取平台证书
     */
    public function Decrypt($associatedData, $nonceStr, $ciphertext)
    {
        $aeskey = config('wxconfig.aeskey');
        $AesUtil = new AesUtil($aeskey);
        $publicKey = $AesUtil->decryptToString($associatedData, $nonceStr, $ciphertext);
        return $publicKey;
    }

    /**
     * 获取sign
     * v3
     * @param $url
     * @param $http_method [POST GET 必读大写]
     * @param $body [请求报文主体（必须进行json编码）]
     * @param $mch_private_key [商户私钥]
     * @param $merchant_id [商户号]
     * @param $serial_no [证书编号]
     * @return string
     */
    public function getAuthorization($url, $http_method, $body)
    {
        $timestamp = time();//时间戳
        $mch_private_key = $this->getPrivatekey();
        $merchant_id = config('wxconfig.sp_mchid');
        $nonce = $this->getNonceStr($length = 32);
        $url_parts = parse_url($url);
        $serial_no = config('wxconfig.api_serial_no');//API证书序列号
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message =
            $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchant_id, $nonce, $timestamp, $serial_no, $sign);
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $header = [
            'Content-Type:application/json',
            'Accept:application/json',
            'User-Agent:*/*',
            'Authorization: ' . $schema . ' ' . $token
        ];
//        halt($header);
        return $header;
    }

    /**
     * 获取商户私钥
     * @return false|resource
     */
    public function getPrivatekey()
    {
        $private_key_file = ('D:\tg\public\ssl\apiclient_key.pem');
        //私钥文件路径 如linux服务器秘钥地址地址：/www/wwwroot/test/key/private_key.pem"
        $mch_private_key = openssl_get_privatekey(file_get_contents($private_key_file));//获取私钥
        return $mch_private_key;
    }

    /**
     * 数据请求
     * @param $url
     * @param array $header 获取头部
     * @param string $post_data POST数据，不填写默认以GET方式请求
     * @return bool|string
     */
    public function http_Request($url, $header = array(), $post_data = "")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);
        if ($post_data != "") {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); //设置post提交数据
        }
        //判断当前是不是有post数据的发
        $output = curl_exec($ch);
        if ($output === FALSE) {
            $output = "curl 错误信息: " . curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }

    /**
     * @param $url
     * @param $data
     * @param array $header
     * @param string $referer
     * @param int $timeout
     * @return bool|string
     * TODO 获取上传id 媒体上传
     */
    public function doCurl($url, $data, $header = array(), $referer = '', $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //避免https 的ssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        // 模拟来源
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            die($error);
        }
        curl_close($ch);
        return $response;
    }

    /**
     * TODO　获取微信Unionid
     */
    public function getWxUnionid($openid)
    {
        $appid = config('wxconfig.gzh_appid');
        $secret = config('wxconfig.gzh_secret');
        $access_token = $this->getAccessToken($appid, $secret);
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=';
        $get_token_url = $this->combineURL($url, [
            'access_token' => $access_token,
            'openid' => $openid,
        ]);
        $WxbaseInfo = $this->httpsRequest($get_token_url);
        return json_decode($WxbaseInfo, true);
    }

    /**
     * 拼接url
     * @param string $baseURL 请求的url
     * @param array $keysArr 参数列表数组
     * @return string           返回拼接的url
     */
    public function combineURL($baseURL, $keysArr)
    {
        $combined = $baseURL . "?";
        $valueArr = array();
        foreach ($keysArr as $key => $val) {
            $valueArr[] = "$key=$val";
        }
        $keyStr = implode("&", $valueArr);
        $combined .= ($keyStr);
        return $combined;
    }

    /**
     * 获取服务器数据
     * @param string $url 请求的url
     * @return  unknown    请求返回的内容
     */
    public function httpsRequest($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     * @param $type
     * @param $appid
     * @param $appsecret
     * 获取全局TOken
     * 如果项目不同对外
     */
    public function getMiniAppAccessToken($appid, $appsecret)
    {
        $tokenKey = $appid . 'mini_access_token';
        $access_token = Cache::get($tokenKey);
        if (empty($tokens)) {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret;
            $html = file_get_contents($url);

            $access_token = json_decode($html, true)['access_token'];
            $cache_token = Cache::set($tokenKey, $access_token, 60 * 110);
        }

        return $access_token;
    }

    /**
     * @param $type
     * @param $appid
     * @param $appsecret
     * 获取全局TOken
     * 获取公众号权限
     */
    public function getGzhAccessToken()
    {
        $accessTokenKey = config('wxconfig.gh_accessTokenKey');
        $wx_access_token = Cache::get($accessTokenKey);
        if (!$wx_access_token) {
            $appid = config('wxconfig.gzh_appid');//
            $secret = config('wxconfig.gzh_secret');//
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $wx_access_token = $this->get_url($url, "json")["access_token"];
            Cache::set($accessTokenKey, $wx_access_token, 7000);
        }
        return $wx_access_token;
    }

    /**
     * @param $url
     * @param $v
     * @return bool|mixed|string
     * 请求数据
     */
    public function get_url($url, $v)
    {
        //http api接口json数据
        $html = file_get_contents($url);
        if ($v == "json") {
            return json_decode($html, true);
        } else {
            return $html;
        }
    }

}