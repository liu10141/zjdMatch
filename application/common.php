<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liut@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件

/*封装是否方法*/
function isYesNo($str)
{
    return $str ? '<span style="color:red"> 是</span>' : '<span > 否</span>';
}

/**
 * 二维数组根据某个字段排序
 * @param array $array 要排序的数组
 * @param string $keys 要排序的键字段
 * @param string $sort 排序类型  SORT_ASC     SORT_DESC
 * @return array 排序后的数组
 */
function arraySort($array, $keys, $sort = SORT_DESC)
{
    $keysValue = [];
    foreach ($array as $k => $v) {
        $keysValue[$k] = $v[$keys];
    }
    array_multisort($keysValue, $sort, $array);
    return $array;
}

/**
 * 模拟post进行url请求
 * @param $url
 * @param $post_data
 * @return string
 */
function http_json_data($url, $post_data, $token, $ispost = 'post')
{
    if (empty($url) || empty($post_data)) {
        return false;
    }
    $postUrl = $url;
    $curlPost = json_encode($post_data);
    $ch = curl_init();//初始化curl
    $header = array();
    $header[] = 'Accept:application/json';
    $header[] = 'Content-Type:application/json';
    $header[] = 'Authorization:' . $token;
    //兼容新版api接口，token在header中请求
    curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, $ispost);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//验证对方的SSL证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//检查声称服务器的证书的身份
    $data = curl_exec($ch);//运行curl
    curl_close($ch);

    return $data;
}

function tree_to_list($tree, $child = '_child', $pid = 0, &$list = array())
{
    if (is_array($tree)) {
        $reffer = array();
        foreach ($tree as $key => $value) {
            $reffer = $value;
            //layui treetable使用
            $reffer['setPid'] = $pid;
            if (isset($reffer[$child])) {
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $value['id'], $list);
            }
            $list[] = $reffer;
        }
    }
    return $list;
}

/**
 * @param $url
 * @param $data
 * @param bool $isToken
 * @return mixed|string
 * 获取有人云token
 */
function interfaceDock($url, $data, $isToken = true)
{
    if ($isToken && !isset($data['token'])) {
        $token = Cache::get('token');
        $post_data = array_merge($data, array('token' => $token));
    } else {
        $token = '';
        $post_data = $data;
    }
    //请求有人云api
    $post_url = config('API_ADDRESS') . $url;
    $dataRes = http_json_data($post_url, $post_data, $token);
    $dataRes = json_decode($dataRes, true);
    return $dataRes;
}


/**
 * 状态
 * @param $id
 * @param $status
 */
function status($id, $status)
{
    $controller = request()->controller();
    $sta = $status == 1 ? 0 : 1;
    $url = url($controller . '/status', ['id' => $id, 'status' => $sta]);
    if ($status == 1) {
        $str = "<a href='javascript:;' title='修改状态' status_url='" . $url . "' onclick='app_status(this)'><span class='label label-success radius'>正常</span></a>";
    } elseif ($status == 0) {
        $str = "<a href='javascript:;' title='修改状态' status_url='" . $url . "' onclick='app_status(this)'><span class='label label-danger radius'>待审核</span></a>";
    }
    return $str;
}

/**
 * 通用化API接口数据输出
 * @param int $status 业务状态码
 * @param string $message 信息提示
 * @param [] $data  数据
 * @param int $httpCode http状态码 默认200
 * @return array
 */
function show($status, $info, $data = [])
{
    ob_start('ob_gzhandler');
    $data =
        [
            'status' => $status,
            'message' => $info,
            'data' => $data,
        ];
    //返回json格式
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * @param $url
 * @param $arr
 * @param $v
 * @return bool|mixed|string
 * post放
 */
function post_json($url, $arr, $v)
{//post提交json数据
    $ch = curl_init();
// print_r($ch);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
    $return = curl_exec($ch);
    curl_close($ch);

    if ($v == "json") {
        return json_decode($return, true);
    } else {
        return $return;
    }
}

/**
 *获取唯一单号
 *算法简单使用重复几率火星撞地球
 * @return string uniqud 单号
 */
function get_orn()
{
    $orn = date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 6);
    return $orn;
    // get_orn();在其他的类里边调用即可！
}

/**
 *$url string 请求的地址
 *$header array [Content-Type:multipart/form-data]
 *$header array []
 */
function curl($url, $header, $data)
{
    // 调用的时候只需要传入具体的参数即可
    //初使化init方法
    $ch = curl_init();
    //指定URL
    curl_setopt($ch, CURLOPT_URL, $url);
    //设定请求后返回结果
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //声明使用POST方式来进行发送
    curl_setopt($ch, CURLOPT_POST, 1);
    //发送什么数据呢
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //忽略证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//   curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
    //忽略header头信息
    curl_setopt($ch, CURLOPT_HEADER, 0);
    // curl_setopt($ch, CURLOPT_HTTPHEADER,$header);

//   CURLOPT_HTTPHEADER => array(
//         "accept: application/json",
//         "apix-key: 您的apix-key",
//         "content-type: application/json"
//     ),
    //设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    //发送请求
    $output = curl_exec($ch);//执行后的结果
    //关闭curl
    curl_close($ch);
    //返回数据
    return $output;
}

//二进制判断文件类型
function post($url, $header = [], $data)
{
    // 调用的时候只需要传入具体的参数即可
    //初使化init方法
    $ch = curl_init();
    //指定URL
    curl_setopt($ch, CURLOPT_URL, $url);
    //设定请求后返回结果
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //声明使用POST方式来进行发送
    curl_setopt($ch, CURLOPT_POST, 1);
    //发送什么数据呢
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //忽略证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //忽略header头信息
    curl_setopt($ch, CURLOPT_HEADER, $header);
    //设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    //发送请求
    $output = curl_exec($ch);//执行后的结果
    //关闭curl
    curl_close($ch);
    //返回数据
    return $output;
}

function check_image_type($image)
{
    $bits = array(
        'JPEG' => "\xFF\xD8\xFF",
        'GIF' => "GIF",
        'PNG' => "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a",
        'BMP' => 'BM',
    );
    foreach ($bits as $type => $bit) {
        if (substr($image, 0, strlen($bit)) === $bit) {
            return $type;
        }
    }
    return 'UNKNOWN IMAGE TYPE';
}

/**
 * @return string
 * 生成邀请码
 */
function make_invite_code()
{
    $code = "ABCDEFGHIGKLMNOPQRSTUVWXYZ";
    $rand = $code[rand(0, 25)] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    for ($a = md5($rand, true), $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV', $d = '', $f = 0;
         $f < 8;
         $g = ord($a[$f]), // ord（）函数获取首字母的 的 ASCII值
         $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],//按位亦或，按位与。
         $f++) ;
    return $d;
}

/**
 * @return string
 * 生成会员兑换码 百万数据不重复
 */
function sp_gm_get_gift_code()
{
    $uniqid = uniqid('gm', true);
    $param_string = $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . time() . rand(10, 9999) . $uniqid;
    $sha1 = sha1($param_string);
    for (
        $a = md5($sha1, true),
        $s = '0123456789abcdefghijklmnopqrstuvwxyz',
        $d = '',
        $f = 0;
        $f < 8;
        $g = ord($a[$f]),
        $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],
        $f++

    ) ;
    return $d;

}

/**
 * @return string
 * 生成会七位数
 *
 */
function sp_gm_get_gift_vip_code()
{
    $uniqid = uniqid('gm', true);
    $param_string = $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . time() . rand(10, 9999) . $uniqid;
    $sha1 = sha1($param_string);
    for (
        $a = md5($sha1, true),
        $s = '0123456789abcdefghijklmnopqrstuvwxyz',
        $d = '',
        $f = 0;
        $f < 7;
        $g = ord($a[$f]),
        $d .= $s[($g ^ ord($a[$f + 7])) - $g & 0x1F],
        $f++

    ) ;
    return $d;

}

/**
 * //php通过0-9随机生成唯一的8位数
 **/
function nonceStr()
{
    $arr = []; //定义一个空数组
    for ($i = 0; $i < 100; $i++) {  //生成100组
        $str = uniqid($i); //根据微秒生成随机16进制字符
        $num = base_convert($str, 16, 8); //将生成的16进制转成8进制
        $number = substr($num, 0, 8); //将8进制的字符，从下标0开始截取前8位数，得到一组唯一的8位数
        //截取生成唯一8位数的开头字符，并进行判断是否为0，如果是0开头的话，就跳出循环
        $if_num = substr($number, 0, 1);
        if ($if_num == 0) {
            continue;
        }
        //判断$arr数组中是否存在生成的$number，存在的话就跳出循环
        if (in_array($number, $arr)) {
            echo "error";
            continue;
        } else { //如果$arr数组中不存在生成的$number，就将$number存入$arr数组
            array_push($arr, $number);
        }
        return $number;
    }
    // echo "<pre>";
    // var_dump($arr);
}

function get_age($birthday)
{
    $birthday = strtotime($birthday);
    //格式化出生时间年月日
    $byear = date('Y', $birthday);
    $bmonth = date('m', $birthday);
    $bday = date('d', $birthday);
    //格式化当前时间年月日
    $tyear = date('Y');
    $tmonth = date('m');
    $tday = date('d');

    //开始计算年龄
    $age = $tyear - $byear;
    if ($bmonth > $tmonth || $bmonth == $tmonth && $bday > $tday) {
        $age--;
    }
    return $age;
}

function getAgeByIdcard($idcard)
{
    $year = substr($idcard, 6, 4);
    $age = date('Y') - $year;

    return $age > 0 ? $age : 0;
}

//黑名单检测 post data
if (!function_exists('curlPost')) {
    function curlPost($url, $header, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5 * 60);
        $output = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($output, true);
        return $data;
    }
}
//face++ 身份证认证
function get_sign()
{
    $apiKey = 'P0jKpk1brzdMRb2Q6G-ZYx0cHKw-oY_u';
    $apiSecret = 'lBX9nflLdpeIlvci7ZZPGSpsh7YcrHEb';
    $rdm = rand();
    $current_time = time();
    $expired_time = $current_time + '3000';
    $srcStr = "a=%s&b=%d&c=%d&d=%d";
    $srcStr = sprintf($srcStr, $apiKey, $expired_time, $current_time, $rdm);
    $sign = base64_encode(hash_hmac('SHA1', $srcStr, $apiSecret, true) . $srcStr);
    return $sign;
}

function curlFilePost($url, $content)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POSTFIELDS => $content,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
        ),
    ));
    $response = curl_exec($curl);
    // file_put_contents('abcd.txt', $response);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        $data = json_decode($response, true);
        return $data;
    }
}

function binary_to_file($file, $content)
{
    $ret = file_put_contents($file, $content);
    return $ret;
}

/** 将xml转为array
 * @param string $xml
 * @throws WxPayException
 */
function ToXml($values)
{
    $reqXml = '';
    $reqXml = '<?xml version="1.0" encoding="UTF-8"?>';
    $reqXml .= '<MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">';
    $reqXml .= '<version>' . $values['version'] . '</version>';
    $reqXml .= '<indAuthContent>';
    $reqXml .= '<merchantId>' . $values['merchantId'] . '</merchantId>';
    $reqXml .= '<terminalId>' . $values['terminalId'] . '</terminalId>';
    $reqXml .= '<customerId>' . $values['customerId'] . '</customerId>';
    $reqXml .= '<externalRefNumber>' . $values['externalRefNumber'] . '</externalRefNumber>';
    $reqXml .= '<pan>' . $values['pan'] . '</pan>';
//		$reqXml.='<expiredDate>'.$values['expiredDate'].'</expiredDate>';  //信用卡传值
//		$reqXml.='<cvv2>'.$values['cvv2'].'</cvv2>';		  //信用卡传值
    $reqXml .= '<cardHolderName>' . $values['cardHolderName'] . '</cardHolderName>';
    $reqXml .= '<idType>' . $values['idType'] . '</idType>';
    $reqXml .= '<cardHolderId>' . $values['cardHolderId'] . '</cardHolderId>';
    $reqXml .= '<phoneNO>' . $values['phoneNO'] . '</phoneNO>';
    $reqXml .= '<bindType>0</bindType>';
    $reqXml .= '</indAuthContent></MasMessage>';
    return $reqXml;
}

/**
 * 将xml转为array
 * @param string $xml
 * @throws WxPayException
 */
function xmlToArray($xml, $isfile = false)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    if ($isfile) {
        if (!file_exists($xml)) return false;
        $xmlstr = file_get_contents($xml);
    } else {
        $xmlstr = $xml;
    }
    $result = json_decode(json_encode(simplexml_load_string($xmlstr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $result;
}

/**
 * 带有头信息的
 *$url string 请求的地址
 *$header array [Content-Type:multipart/form-data]
 *$header array []
 */
function curl_header($url, $header, $data)
{
    // 调用的时候只需要传入具体的参数即可
    //初使化init方法
    $ch = curl_init();
    //指定URL
    curl_setopt($ch, CURLOPT_URL, $url);
    //设定请求后返回结果
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //声明使用POST方式来进行发送
    curl_setopt($ch, CURLOPT_POST, 1);//默认post提交
    //发送什么数据呢
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //忽略证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    //发送请求
    $output = curl_exec($ch);//执行后的结果

    //关闭curl
    curl_close($ch);
    //返回数据
    return $output;
}

/**
 * @param $url
 * @param $data
 * @return mixed
 * u钱包
 */
function curl_post_https($url, $data)
{ // 模拟提交数据函数
    $aHeader = array('Content-Type:application/x-www-form-urlencoded', 'charset:utf-8', 'Accept:application/json', 'X-APFormat:json');
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
//    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
//    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36'); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeader);
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        echo 'Errno' . curl_error($curl);//捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据，json格式
}

/**
 * get请求
 * @param $url
 * @param $header
 * @return bool|string
 */
function curlGet($url, $header = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置头信息
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $output = curl_exec($ch);

    curl_close($ch);

    return $output;
}

/**
 * 第三方支付请求接口
 * @param $url
 * @param $str
 * @return bool|string
 */
function apiUrl($url, $str)
{
    $header[] = "Content-type: text/xml;charset=utf-8";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $output = curl_exec($ch);

    return $output;
}

function tp_curl($url, $header = [], $data)
{
    //调用的时候只需要传入具体的参数即可
    //初使化init方法
    $ch = curl_init();
    //指定URL
    curl_setopt($ch, CURLOPT_URL, $url);
    //设定请求后返回结果
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //声明使用POST方式来进行发送
    curl_setopt($ch, CURLOPT_POST, 1);
    //发送什么数据呢
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //忽略证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//   curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
    //忽略header头信息
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//   CURLOPT_HTTPHEADER => array(
//         "accept: application/json",
//         "apix-key: 您的apix-key",
//         "content-type: application/json"
//     ),
    //设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    //发送请求
    $output = curl_exec($ch);//执行后的结果
    //关闭curl
    curl_close($ch);
    //返回数据
    return json_decode($output, true);
}

/**
 * 通用post提交数据
 * $url 地址
 * $data 参数  数组类型
 * $header = []  header 头信息 可以不传
 */
if (!function_exists('curlPostY')) {
    function curlPostY($url, $data, $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if (count($header) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5 * 60);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}