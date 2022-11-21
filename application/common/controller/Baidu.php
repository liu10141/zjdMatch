<?php


namespace app\common\controller;


use baidu\AipOcr;
use think\Controller;

class Baidu extends Controller
{
    /**
     *输入二进制图片也可以
     * 识别营业执照api0
     * @param $filepath
     * @return
     * 参数           是否必须    类型              说明
     * log_id           是       uint64    请求标识码，随机数，唯一。
     * words_result_num 是       uint32    识别结果数，表示words_result的元素个数
     * words_result     是       object{}  识别结果
     * left             是       uint32    表示定位位置的长方形左上顶点的水平坐标
     * top              是       uint32    表示定位位置的长方形左上顶点的垂直坐标
     * width            是       uint32    表示定位位置的长方形的宽度
     * height           是       uint32    表示定位位置的长方形的高度
     * words            否       string    识别结果字符串
     */
    public function business_license_ocr($filepath)
    {
        $fp = fopen($filepath, 'rb');
        $content = fread($fp, filesize($filepath)); //二进制数据
        //halt($content);
        $appId = config('baidu.appId');
        $apiKey = config('baidu.apiKey');
        $secretKey = config('baidu.secretKey');
        $client = new  AipOcr($appId, $apiKey, $secretKey);
        //如果有可选参数
        $options = array();
        $options["language_type"] = "CHN_ENG";
        $options["detect_direction"] = "true";
        $options["detect_language"] = "true";
        $options["probability"] = "true";
        $result = $client->businessLicense($content, $options);
//        halt($result);
        foreach ($result['words_result'] as $k => $value) {
            $data[$k] = $value['words'];
        }
        return $data;
    }

    /**
     * 识别身份证信息
     */
    public function idcard_ocr($filepath)
    {
        $fp = fopen($filepath, 'rb');
        $content = fread($fp, filesize($filepath)); //二进制数据
        $appId = config('baidu.appId');
        $apiKey = config('baidu.apiKey');
        $secretKey = config('baidu.secretKey');
        $client = new  AipOcr($appId, $apiKey, $secretKey);
        //如果有可选参数
        $options = array();
        $options["detect_direction"] = "CHN_ENG";
        $options["detect_risk"] = "true";
        $options["detect_photo"] = "true";
        $options["detect_rectify"] = "true";
        $idCardSide = 'front';
        $result = $client->idcard($content, $idCardSide, $options = array());

        foreach ($result['words_result'] as $k => $value) {
            $data[$k] = $value['words'];
        }

        if (empty($data['姓名']) || empty($data['公民身份号码'])) {
            return false;
        }
        return $data;
    }

    /**
     * 识别车牌号
     * 00
     */
    public function car_ocr($filepath)
    {
        $fp = fopen($filepath, 'rb');
        $content = fread($fp, filesize($filepath)); //二进制数据
        $appId = config('baidu.appId');
        $apiKey = config('baidu.apiKey');
        $secretKey = config('baidu.secretKey');
        $client = new  AipOcr($appId, $apiKey, $secretKey);
        //如果有可选参数
        $result = $client->licensePlate($content, $options = array());
            $data['car_no'] = isset($result['words_result']['number']) ? $result['words_result']['number'] : '';
            $data['color'] = isset($result['words_result']['color']) ? $result['words_result']['color'] : '';
            return $data;
    }

    /**
     * 识别身份证信息
     * 识别身份证信息
     * 上传图片地址远程地址
     */
    public function idcardOcr($filepath)
    {

        $fp = fopen($filepath, 'rb');
        $content = fread($fp, filesize($filepath)); //二进制数据
        $appId = config('baidu.appId');
        $apiKey = config('baidu.apiKey');
        $secretKey = config('baidu.secretKey');
        $client = new  AipOcr($appId, $apiKey, $secretKey);
        //如果有可选参数
        $options = array();
        $options["detect_direction"] = "CHN_ENG";
        $options["detect_risk"] = "true";
        $options["detect_photo"] = "true";
        $options["detect_rectify"] = "true";
        $idCardSide = 'front';
        $result = $client->idcard($content, $idCardSide, $options = array());

        foreach ($result['words_result'] as $k => $value) {
            $data[$k] = $value['words'];
        }

        if (empty($data['姓名']) || empty($data['公民身份号码'])) {
            return false;
        }
        return $data;
    }

    /**
     * 识别银行卡信息
     * 参数               类型     是否必须         说明
     * log_id           uint64      是      请求标识码，随机数，唯一。
     * direction        int32       否      图像方向，当 detect_direction = true 时，返回该参数。
     * - -1:未定义;
     * - 0:正向;
     * - 1: 逆时针90度;
     * - 2:逆时针180度;
     * - 3:逆时针270度
     *   result           object    是       返回结果
     * + bank_card_number string    是       银行卡卡号
     * + valid_date       string    是       有效期
     * + bank_card_type   uint32    是       银行卡类型，0:不能识别; 1: 借记卡; 2: 信用卡
     * + bank_name        string    是       银行名，不能识别时为空
     */
    public function bankcard_ocr($filepath)
    {
        $fp = fopen($filepath, 'rb');
        $content = fread($fp, filesize($filepath)); //二进制数据
        $appId = config('baidu.appId');
        $apiKey = config('baidu.apiKey');
        $secretKey = config('baidu.secretKey');
        $client = new  AipOcr($appId, $apiKey, $secretKey);
        //如果有可选参数
        $options = array();
        $options["detect_direction"] = "false";
        $result = $client->bankcard($content, $options = array());
        $data = $result['result'];
//halt($data);
        if (empty($data['bank_name'])) {
            return false;
        }
        return $data;
    }

}