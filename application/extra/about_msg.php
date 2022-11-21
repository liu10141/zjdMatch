<?php
/**
 * 阿里云相关的配置
 * Created by PhpStorm.
 * User: baidu
 * Date: 17/7/31
 * Time: 上午12:37
 */
return [
    'appKey' => '24528979',
    'secretKey' => 'ec6d90dc7e93b4cc824183f71710e1ee',
    'signName' => '',
    'templateCode' => 'SMS_75915048',
    'identify_time' => 12000000,
    'top_sdk_work_dir' => '/tmp/',
    'account' => 'dh90871',
    'password' => '33FgV3YS',
];
/*     ======================================
               如下代码上线删除
               http://www.dh3t.com/json/sms/Submit
               以下json内容为提交请求数据格式：
               {"account":"dh****",
               "password":"e717ebfd5271ea4a98bd38653c01113d",
               "msgid":"2c92825934837c4d0134837dcba00150",
               "phones":"1571166****",
               "content":"您好，您的手机验证码为：430237。随机数 mt_rand(100000,999999)",
               "sign":"【****】","subcode":"",
               "sendtime":"201405051230"
               }
               字段说明
               account：用户账号；
               password：账号密码，需采用MD5加密(32位小写)；
               msgid：该批短信编号(32位UUID)，需保证唯一，选填；
               phones：接收手机号码，多个手机号码用英文逗号分隔，最多500个，必填；
               content：短信内容，最多350个汉字，必填,内容中不要出现【】[]这两种方括号，该字符为签名专用；
               sign：短信签名，该签名需要提前报备，生效后方可使用，不可修改，必填
               ，示例如：【大汉三通】；
               subcode：短信签名对应子码(大汉三通提供)+自定义扩展子码(选填)，必须是数字，选填，未填使用签名对应子码，通常建议不填；
               sendtime：定时发送时间，格式yyyyMMddHHmm，为空或早于当前时间则立即发送；
        */
