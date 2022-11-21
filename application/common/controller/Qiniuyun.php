<?php


namespace app\common\controller;


use Qiniu\Auth;
use Qiniu\Http\Client;
use Qiniu\Processing\PersistentFop;
use Qiniu\Rtc\AppClient;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use think\Controller;

class Qiniuyun extends Controller
{
    /**
     * 获取视频会议的token
     * 自己公司的用户方可进入
     * 需要加入的用户id请求
     * 如果room相同但是userid 不同 token也是进不去的
     * $permission = 'admin';
     *
     */
    public function get_room_token($roomName, $userId, $permission)
    {
        $accessKey = config('qiniu.accessKeyId');  //秘钥
        $secretKey = config('qiniu.accessSecret');  //秘钥
        $auth = new Auth($accessKey, $secretKey);
        $room_token_obj = new AppClient($auth);
        $appId = 'enayapnrx';
        $expireAt = strtotime("+1 day");
        return $room_token = $room_token_obj->appToken($appId, $roomName, $userId, $expireAt, $permission);
    }

    /**
     * @param $delFileName
     * @return bool
     * 删除--七牛云资源
     */
    public function qiniu_delfile($delFileName, $bucket)
    {
        $accessKey = config('qiniu.accessKeyId');  //秘钥
        $secretKey = config('qiniu.accessSecret');  //秘钥
        $auth = new Auth($accessKey, $secretKey);  //实例化
        $bucketMgr = new BucketManager($auth);//空间管理
        $err = $bucketMgr->delete($bucket, $delFileName);//删除文件
        if (empty($err)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 七牛云压缩文件
     * 压缩文件 保存的空间
     * $data 带压缩的文件
     * $bucket 压缩后需要保存的空间
     */
    public function qiniu_mkzip($data, $bucket)
    {
        $accessKey = config('qiniu.accessKeyId');  //秘钥
        $secretKey = config('qiniu.accessSecret');  //秘钥
        $key = 'test.jpg';//七牛云不能删除此文件
        $auth = new Auth($accessKey, $secretKey);
        // 异步任务的队列， 去后台新建： https://portal.qiniu.com/mps/pipeline
        $pipeline = '';
        $notify_url = 'null';
        $force = false;
        $pfop = new PersistentFop($auth, $bucket, $pipeline, $notify_url, $force);
        // 进行zip压缩的url
        $countfile = count($data);
        //压缩后的key  自己定义key
        $zipKey = 'MFYZIP' . date('YmdHis') . rand(1000, 9999) . '.zip';
        $fops = 'mkzip/2';
        for ($i = 0; $i < $countfile; $i++) {
            $fops .= '/url/' . base64_urlSafeEncode($data[$i]);
        }

        $fops .= '|saveas/' . base64_urlSafeEncode("$bucket:$zipKey");
        list($id, $err) = $pfop->execute($key, $fops);
        if ($err != null) {
            return ['zipkey' => ''];
        } else {
            $flag = true;
            $loopCount = 15;
            while ($flag) {
                --$loopCount;
                if ($loopCount < 1) {
                    return ['zipkey' => ''];
                }
                sleep(2);
                file_put_contents("log.txt", date('YmdHis') . PHP_EOL, FILE_APPEND);
                $url = "http://api.qiniu.com/status/get/prefop?id=$id";
                $res = curlGet($url);
                $res = json_decode($res, true);
                if (isset($res['code']) && $res['code'] == 0) {
                    $flag = false;
                    return ['zipkey' => 'http://mfyfile.greatorange.cn/' . $zipKey];
                }

            }
        }
    }

    /**
     * 上传base64位图片到七牛云
     * $image base64位图片流
     * upload.qiniup.com 上传域名适用于华东空间。
     * 华北空间使用 upload-z1.qiniu.com，
     * 华南空间使用 upload-z2.qiniu.com，
     * 北美空间使用 upload-na0.qiniu.com。
     * $rand = rand(100000, 999999);
     * $now = date('YmdHis/');
     *  $name = 'MFYSign/' . $now . $rand . '.jpg';
     */
    public function qiniu_upbase64($image, $bucket, $name)
    {
        $accessKey = config('qiniu.accessKeyId');  //秘钥
        $secretKey = config('qiniu.accessSecret');  //秘钥
        $auth = new Auth($accessKey, $secretKey);
        $upToken = $auth->uploadToken($bucket, null, 3600);//获取上传所需的token
        $num = strpos($image, ',');
        $image = substr($image, $num + 1);
        $str = isset($image) ? $image : false;
        //生成图片key
        $key = base64_encode($name);
        if ($str) {
            $qiniu = $this->phpCurlImg("http://upload-z2.qiniu.com/putb64/-1/key/" . $key, $str, $upToken);
            $qiniuArr = json_decode($qiniu, true);
            if (!empty($qiniuArr['key']) && $qiniuArr['key'] == $name) {
                return $imaurl['url'] = $qiniuArr['key'];
            } else {
                return $imaurl['url'] = '';
            }
        }
        return $imaurl['url'] = '';
    }

    /**
     * @param $image 图片路径
     * @param $bucket 空间名称
     * @param $name 文件名称
     */
    public function qiniu_upfile($image, $bucket, $name)
    {
        $accessKey = config('qiniu.accessKeyId');  //秘钥
        $secretKey = config('qiniu.accessSecret');  //秘钥
        $auth = new Auth($accessKey, $secretKey);  //实例化
        $token = $auth->uploadToken($bucket);
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($token, $name, $image);
        if ($err !== null) {
            return $imaurl['url'] = '';
        } else {
            $key = $ret['key'];
            return $imaurl['url'] = $key;
        }
    }

    //七牛base64上传方法
    public function phpCurlImg($remote_server, $post_string, $upToken)
    {
        $headers = array();
        $headers[] = 'Content-Type:application/octet-stream';
        $headers[] = 'Authorization:UpToken ' . $upToken;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 七牛云发送短信
     */
    public function qiniu_send_sms($template_id, $mobiles, $parameters = null)
    {
        $baseURL = 'https://sms.qiniuapi.com/';
        $params['template_id'] = $template_id;
        $params['mobiles'] = $mobiles;
        if (!empty($parameters)) {
            $params['parameters'] = $parameters;
        }
        $body = json_encode($params);
        $url = $baseURL . 'message';
        $ret = $this->post($url, $body);
        return $ret;
    }

    /*
    * 创建模板
    * name  : 模板名称 string 类型 ，必填
    * template:  模板内容 string  类型，必填
    * type: 模板类型 string 类型，必填，
      取值范围为: notification (通知类短信), verification (验证码短信), marketing (营销类短信)
    * description:  申请理由简述 string  类型，必填
    * signature_id:  已经审核通过的签名 string  类型，必填
    * @return: 类型 array {
        "template_id": string
                }
    */
    public function createTemplate($name, $template, $type, $description, $signture_id)
    {
        $params['name'] = $name;
        $params['template'] = $template;
        $params['type'] = $type;
        $params['description'] = $description;
        $params['signature_id'] = $signture_id;
        $body = json_encode($params);
        $url = $this->baseURL . 'template';
        $ret = $this->post($url, $body);
        return $ret;
    }

    private function post($url, $body, $contentType = 'application/json')
    {
        $accessKey = config('qiniu.accessKeyId');  //秘钥
        $secretKey = config('qiniu.accessSecret');  //秘钥
        $auth_obj = new Auth($accessKey, $secretKey);
        $rtcToken = $auth_obj->authorizationV2($url, "POST", $body, $contentType);
        $rtcToken['Content-Type'] = $contentType;
        $ret = Client::post($url, $body, $rtcToken);
//        halt($ret);
        if (!$ret->ok()) {
            return false;

        }
        $r = ($ret->body === null) ? array() : $ret->json();
        return array($r, null);
    }


}