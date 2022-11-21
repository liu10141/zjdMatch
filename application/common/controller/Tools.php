<?php

namespace app\common\controller;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use PHPMailer\PHPMailer\PHPMailer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Qiniu\Rtc\AppClient;
use think\Cache;
use think\Controller;

//阿里云类库
//七牛云类库0

class Tools extends Controller
{
    /**
     * 阿里大鱼发送短信验证码 0
     */
    public function sendsms()
    {
        $accessKeyId = config('about_msg.accessKeyId');
        $accessSecret = config('about_msg.accessSecret');
        AlibabaCloud::accessKeyClient($accessKeyId, $accessSecret)
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                    ],
                ])
                ->request();
            print_r($result->toArray());
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }

    }

    /**
     * 获取九达请求token
     */
    public function getjdToken()
    {
        $cachekey = config('jd.jd_token');
        $token = Cache::get($cachekey);
        if (empty($token)) {
            $url = 'https://api.jiudacheng.com/proxy/user/login';
            $mobile = config('jd.account');
            $passWord = config('jd.password');
            $post_data = [
                'mobile' => $mobile,
                'passWord' => $passWord,

            ];
            $dataRes = http_json_data($url, $post_data, '');
            $dataRes = json_decode($dataRes, true);
            if ($dataRes['code'] == '000000') {
                $token = $dataRes['data']['token'];
                Cache::set($cachekey, $token, 3550);
            }
        }
        return $token;


    }


    /**
     * 生成二维码
     * $value
     * http://mfyfile.greatorange.cn/logo.png
     * logo 如果不穿就为空
     */
    public function create_code($data)
    {
        $value = $data['value'];
        $logo = isset($data['logo']) ? $data['logo'] : '';
        $imgDir = './' . 'uploads/' . 'code/';
        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0777, true);
        }
        $filename = 'QR_CODE_' . mt_rand(10000, 99999) . time() . '.png';
        $filepath = $imgDir . $filename;
        //todo 入库;
        $code = qrcode($value, $filepath, $level = 3, $matrixPointSize = 4, $logo);
//        $res=$this->upload($filepath,$filename);//上传七牛云
        echo show(config('code.success'), '二维码', ['base64_str' => $code]);
//        unlink($filepath);
        die;
    }

    /**
     * PhpSpreadshee 类库插件
     * 教程地址 https://www.jianshu.com/p/10e1f047f2bd
     * $row 从第几行开始筛选数据
     * $index 必须按照顺序添加
     * composer require phpoffice/phpspreadsheet:1.6.*
     * $indexArray=['name','sex','age','status','tom'];
     * 必须要与表格里边的字段一致;
     * 设置入库字段
     */
    public function SaveExcel($xlscFile, $row, $indexArray, $reqIndex)
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($xlscFile); //载入excel表格
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow(); // 总行数
//        $highestColumn = $worksheet->getHighestColumn(); // 总列数// e.g. 5
//        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); //共有几列
        $lines = $highestRow - $row;//减去几行
        if ($lines == 0) {
            return [];
        }
        //索引字段值
        $countIndex = count($indexArray);
//        halt($highestColumnIndex);
//        if ($countIndex != $highestColumnIndex) {
//            exit('请检查Excel表格中列的数量与数据库字段是否匹配');
//        }
        for ($row; $row <= $highestRow; ++$row) {
            for ($index = 0; $index < $countIndex; ++$index) {
                $array[$indexArray[$index]] = $worksheet->getCellByColumnAndRow($index + 1, $row)->getValue(); //姓名
            }
            if (!implode('', $array)) {
                continue;
            } else {
                $indexRes = $this->CheckIndex($array, $reqIndex);
                if ($indexRes) {
                    $data[] = $array;
                }
            }
        }
        return $data;
    }

    /**
     * 检测数组
     * 必填字段
     */
    public function CheckIndex($array, $reqIndex)
    {
//        halt($array);
        $ArrayCount = count($reqIndex);
        $flag = true;
        for ($i = 0; $i < $ArrayCount; $i++) {
            if (empty($array[$reqIndex[$i]])) {
                echo show(config('code.error'), '请检查表格中的必填项数据', ['code' => 201]);
                die;
            }
        }
        return $flag;
    }


    /**
     * 腾讯获取IP归属地
     * 获取IP地址的归属地
     * 请求方式 GET
     * 腾讯的地址IP
     * 请求地址　https://apis.map.qq.com/ws/location/v1/ip
     * 参数　ｉｐ　ｋｅｙ
     * 如果异常发送微信通知切换key
     */
    public function get_ip_adds($ip)
    {
        $baseurl = config('tencent.ipurl');
        $key = config('tencent.key');
        $getData =
            [
                'ip' => $ip,
                'key' => $key,
            ];
        $url = $baseurl . http_build_query($getData);
        $res = curlGet($url, $header = []);
        $res_array = json_decode($res, true);
        if ($res_array['status'] == 0) {
            $data =
                [
                    'nation' => $res_array['result']['ad_info']['nation'],
                    'province' => $res_array['result']['ad_info']['province'],
                    'city' => $res_array['result']['ad_info']['city'],
                    'district' => $res_array['result']['ad_info']['district'],
                    'cityCode' => $res_array['result']['ad_info']['adcode'],
                ];
            return $data;
        }
        return false;
    }

    /**
     * @param $ip
     * @return array|bool
     * 发送邮件
     */
    public function send_mail($to, $title, $content, $from)
    {
        $mail = new PHPMailer();

        //是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
        //$mail->SMTPDebug = 1;

        //使用smtp鉴权方式发送邮件
        $mail->isSMTP();

        //smtp需要鉴权 这个必须是true
        $mail->SMTPAuth = true;

        //链接qq域名邮箱的服务器地址
        $mail->Host = 'smtp.163.com';

        //设置使用ssl加密方式登录鉴权
        $mail->SMTPSecure = 'ssl';

        //设置ssl连接smtp服务器的远程服务器端口号 可选465或587
        $mail->Port = 465;

        //设置smtp的hello消息头 这个可有可无 内容任意
        $mail->Helo = '您收到一封来自投递给HR的邮件';

        //设置发件人的主机域 可有可无 默认为localhost 内容任意，建议使用自己的域名
        $mail->Hostname = '';

        //设置发送的邮件的编码 可选GB2312 或utf-8 测试发现utf8在360浏览器收信下可能会乱码
        $mail->CharSet = 'UTF-8';

        //设置发件人姓名（昵称） 任意内容，显示在收件人邮件的发件人邮箱地址前的发件人姓名
        $mail->FromName = '技术部-招聘简历';

        //smtp登录的账号 这里填入字符串格式的QQ号即可,x代表阿拉伯数字（你的QQ号）
        $mail->Username = '15939918942@163.com';

        //smtp登录的密码；使用生成的授权码（就刚才叫你保存的最新的授权码）不要听信什么这那的“独立密码”，//因为我测试报错提示就是SMTP授权码不正确,x代表授权码（刚让保存的）
        $mail->Password = 'MUWRELDDMYGZZBCA';

        //设置发件人邮箱地址 这里填入上述提到的“发件人邮箱”,x代表阿拉伯数字（你的QQ号）
        $mail->From = $from;

        //邮件正文是否为html编码 注意此处是一个方法 不再是属性 true或false
        $mail->isHTML(true);

        //设置收件人邮箱地址 该方法有两个参数 第一个参数为收件人邮箱地址 第二参数为给该地址设置的昵称 不同的邮箱系统会自动进行处理变动 第二个参数会在发送成功的邮件收件人那边显示
        $mail->addAddress($to, 'HR小姐姐');
        //添加多个收件人 则多次调用方法即可
        //$mail->addAddress('xxxxxx@gmail.com', 'xxxGooGle邮箱');

        //添加该邮件的主题
        $mail->Subject = $title;

        //添加邮件正文 上方将isHTML设置成了true，则可以是完整的html字符串 如：使用file_get_contents函数读取本地的html文件
        $mail->Body = $content;

        //为该邮件添加附件 该方法也有两个参数 第一个参数为附件存放的目录（相对目录、或绝对目录均可） 第二参数为在邮件附件中该附件的名称
//        $mail->addAttachment('./apply/aop.zip', 'login.zip');

        //同样该方法可以多次调用 上传多个附件
//        $mail->addAttachment('./apply/img/1.png', '1.png');

        //发送命令 返回布尔值
        //PS：经过测试，如果收件人不存在，若不出现错误依然返回true 也就是说在发送之前 自己需要些方法实现检测该邮箱是否真实有效
        $status = $mail->send();
        halt($status);

    }

    /**+
     * 导出excel 表格
     */
    public function exportExcel($remark, $sheetTitle, $filename)
    {

        $data = [
            ['title_A' => '张三', 'title_B' => '15936666661', 'title_C' => '中国银行',
                'title_D' => '62121212121212121', 'title_E' => '321101111111123521',
                'title_F' => '100', 'title_G' => '结算'],
        ];
        $title = ['姓名', '手机号', '所属银行', '银行卡号', '身份证号码', '结算费用', '备注'];
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle($sheetTitle);
        // 使用 setCellValueByColumnAndRow
        //设置单元格内容
        //设置表头 备注
        $sheet->setCellValueByColumnAndRow(1, 1, $remark);
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(30);
        foreach ($title as $key => $value) {
            // 单元格内容写入
            $sheet->setCellValueByColumnAndRow($key + 1, 2, $value);
        }
        // sheet1 基础安全需求
        $row = 3; // 从第二行开始
        $stringArray = ['title_D', 'title_E'];
        foreach ($data as $item) {
            $column = 1;
            foreach ($item as $key => $value) {
                // halt($key);
                if (in_array($key, $stringArray)) {
                    $sheet->setCellValueByColumnAndRow($column, $row, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                } else {
                    $sheet->setCellValueByColumnAndRow($column, $row, $value);
                }
                $column++;
            }
            $row++;
        }
        $sheet->mergeCells('A1:G1');
        //设定样式
        //所有sheet的表头样式 加粗
        $font = [
            'font' => [
                'bold' => true,
            ],
        ];
        $sheet->getStyle('A1:Z1')->applyFromArray($font);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(25);
        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /**
     * 微信授权登录
     */
    public function wxlogin($code, $appid, $appsecret)
    {
        // 通过code获取access_token
        $get_token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $appsecret . "&code={$code}&grant_type=authorization_code";
        $token_info = $this->https_request($get_token_url);
        $token_info = json_decode($token_info, true);
        if (isset($token_info['errcode'])) {
            $this->errCode = $token_info['errcode'];
            $this->errMsg = $token_info['errmsg'];
            return false;
        }
        // 通过access_token和openid获取用户信息
        $get_userinfo_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $token_info['access_token'] . '&openid=' . $token_info['openid'] . '&lang=zh_CN';
        $userinfo = $this->https_request($get_userinfo_url);
        $userinfo = json_decode($userinfo, true);
        if (isset($userinfo['errcode'])) {
            $this->errCode = $userinfo['errcode'];
            $this->errMsg = $userinfo['errmsg'];
            return false;
        }
        return $userinfo;
    }


}