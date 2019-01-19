<?php
/**
 *  ==================================================================
 *        文 件 名: Rsa.php
 *        概    要: OpensslRsa 加密、解密、签名、验签类
 *        作    者: IT小强
 *        创建时间: 2018-12-26 21:37:17
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use kelove\traits\SingleModelTrait;

/**
 * OpensslRsa 加密、解密、签名、验签类
 * Class Rsa
 * @package kelove\util
 */
class Rsa
{
    use SingleModelTrait;
    
    /**
     * 私钥签名
     * @param string $data - 待签名数据
     * @param string $privateKey - 私钥
     * @return string - 签名
     */
    public function sign(string $data, string $privateKey): string {
        $privateKey = $this->getPrivateKey($privateKey);
        openssl_sign($data, $sign, $privateKey);
        openssl_free_key($privateKey);
        return base64_encode($sign);
    }
    
    /**
     * 公钥验签
     * @param string $data - 明文
     * @param string $sign - 密文
     * @param string $publicKey - 公钥
     * @return bool
     */
    public function checkSign(string $data, string $sign, string $publicKey): bool {
        $publicKey = $this->getPublicKey($publicKey);
        $sign = base64_decode($sign);
        return (bool)openssl_verify($data, $sign, $publicKey);
    }
    
    /**
     * OpensslRsa加密
     * @param string $data - 明文
     * @param string $publicKey - 公钥
     * @return bool|string 解密失败返回false ，成功返回解密内容
     */
    public function encrypt(string $data, string $publicKey): string {
        $data = str_split($data, 117);
        $key = $this->getPublicKey($publicKey);
        $encrypt = '';
        foreach ($data as $v) {
            openssl_public_encrypt($v, $encryptedTemp, $key);
            $encrypt .= $encryptedTemp;
        }
        return base64_encode($encrypt);
    }
    
    /**
     * OpensslRsa解密
     * @param $data - 密文
     * @param string $privateKey - 私钥
     * @return bool|string 解密失败返回false ，成功返回解密内容
     */
    public function decrypt(string $data, string $privateKey): string {
        // 解密原始数据
        $data = base64_decode($data);
        // 获得私钥
        $key = $this->getPrivateKey($privateKey);
        // 拆分数据
        $data = str_split($data, 256);
        $decrypted = '';
        foreach ($data as $v) {
            openssl_private_decrypt($v, $decryptedTemp, $key);
            $decrypted .= $decryptedTemp;
        }
        return $decrypted;
    }
    
    /**
     * 获取私钥
     * @param string $privateKey - 私钥文件路径/私钥字符串
     * @return bool|resource|string
     */
    protected function getPrivateKey(string $privateKey) {
        //读取私钥
        $path = realpath($privateKey);
        if (is_file($path)) {
            $key = file_get_contents($path);
        } else {
            $key = "-----BEGIN RSA PRIVATE KEY-----\n";
            $key .= $privateKey . "\n";
            $key .= "-----END RSA PRIVATE KEY-----";
        }
        $key = openssl_pkey_get_private($key);
        return $key;
    }
    
    /**
     * 获取公钥
     * @param string $publicKey - 公钥文件路径/公钥字符串
     * @return bool|resource|string
     */
    protected function getPublicKey(string $publicKey) {
        // 获取公匙
        $path = realpath($publicKey);
        if (is_file($path)) {
            $key = file_get_contents($path);
        } else {
            $key = "-----BEGIN PUBLIC KEY-----\n";
            $key .= $publicKey . "\n";
            $key .= "-----END PUBLIC KEY-----";
        }
        $key = openssl_pkey_get_public($key);
        return $key;
    }
}