<?php

namespace app\common\lib;

/**
 * aes 加密 解密类库
 * @by singwa
 * Class Aes
 * @package app\common\lib
 */
class Aes
{

    private $key = null;

    /**
     *
     * @param $key 密钥
     * @return String
     */
    public function __construct()
    {
        // 需要小伙伴在配置文件app.php中定义aeskey
        $this->key = config('app.aeskey');
    }

    /**
     * 加密
     * @param String input 加密的字符串
     * @param String key   解密的key
     * @return HexString
     */
    public function encrypt($input = '')
    {
        try {
            //openssl_encrypt 加密不同Mcrypt，对秘钥长度要求，超出16加密结果不变
            $data = openssl_encrypt($input, 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA);
            $data = strtolower(bin2hex($data));
            return $data;
        } catch (\Exception $e) {
            return '';
        }


    }

    /**
     * 解密
     * @param String input 解密的字符串
     * @param String key   解密的key
     * @return String
     */
    public function decrypt($sStr)
    {
        try {
            $decrypted = openssl_decrypt(hex2bin($sStr), 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA);
            return $decrypted;
        } catch (\Exception $e) {
            return '';
        }

    }

}