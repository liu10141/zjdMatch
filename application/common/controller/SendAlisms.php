<?php


namespace app\common\controller;


use think\Controller;

class SendAlisms extends Controller
{
    // 保存错误信息

    public $error;

    private function percentEncode($string)
    {

        $string = urlencode($string);

        $string = preg_replace('/\+/', '%20', $string);

        $string = preg_replace('/\*/', '%2A', $string);

        $string = preg_replace('/%7E/', '~', $string);

        return $string;

    }

    /**
     * 签名
     *
     * @param unknown $parameters
     * @param unknown $accessKeySecret
     * @return string
     */

    private function computeSignature($parameters, $accessKeySecret)
    {

        ksort($parameters);

        $canonicalizedQueryString = '';

        foreach ($parameters as $key => $value) {
            // var_dump($value);
            $canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);

        }
        // halt($canonicalizedQueryString);
        $stringToSign = 'GET&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));

        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));

        return $signature;

    }

    /**
     * @param unknown $mobile
     * @param unknown $verify_code
     * 发送短信验证码
     *
     */

    public function send_verify($mobile, $verify_code)
    {
        // 配置参数
        $accessKeyId = config('alipay.accessKeyId');

        $accessKeySecret = config('alipay.accessKeySecret');

        $signName = config('alipay.signName');

        $templateCode = 'SMS_195980038';

        $params = array(   //此处作了修改

            'SignName' => $signName,

            'Format' => 'JSON',

            'Version' => '2017-05-25',

            'AccessKeyId' => $accessKeyId,

            'SignatureVersion' => '1.0',

            'SignatureMethod' => 'HMAC-SHA1',

            'SignatureNonce' => uniqid(),

            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),

            'Action' => 'SendSms',

            'TemplateCode' => $templateCode,

            'PhoneNumbers' => $mobile,

            'TemplateParam' => '{"code":"' . $verify_code . '"}'
        );

        // 计算签名并把签名结果加入请求参数

        $params ['Signature'] = $this->computeSignature($params, $accessKeySecret);

        // 发送请求（此处作了修改）

        $url = 'http://dysmsapi.aliyuncs.com/?' . http_build_query($params);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;

    }

    /**
     * 获取详细错误信息
     *
     * @param unknown $status
     */

    public function getErrorMessage($status)
    {

        // 阿里云的短信 乱八七糟的(其实是用的阿里大于)
        // https://api.alidayu.com/doc2/apiDetail?spm=a3142.7629140.1.19.SmdYoA&apiId=25450

        $message = array(

            'InvalidDayuStatus.Malformed' => '账户短信开通状态不正确',

            'InvalidSignName.Malformed' => '短信签名不正确或签名状态不正确',

            'InvalidTemplateCode.MalFormed' => '短信模板Code不正确或者模板状态不正确',

            'InvalidRecNum.Malformed' => '目标手机号不正确，单次发送数量不能超过100',

            'InvalidParamString.MalFormed' => '短信模板中变量不是json格式',

            'InvalidParamStringTemplate.Malformed' => '短信模板中变量与模板内容不匹配',

            'InvalidSendSms' => '触发业务流控',

            'InvalidDayu.Malformed' => '变量不能是url，可以将变量固化在模板中'

        );

        if (isset ($message [$status])) {

            return $message [$status];

        }

        return $status;

    }

}