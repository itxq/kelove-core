<?php
/**
 *  ==================================================================
 *        文 件 名: Encrypt.php
 *        概    要: 字符串加密、解密
 *        作    者: IT小强
 *        创建时间: 2019-01-01 20:04:04
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use kelove\traits\SingleModelTrait;

/**
 * 字符串加密、解密
 * Class Encrypt
 * @package kelove\util
 */
class Encrypt
{
    use SingleModelTrait;
    
    /**
     * @var string 动态密钥
     */
    protected $key = 'kelove_encrypt_key';
    
    /**
     * @var int 密钥有效期
     */
    protected $time = 0;
    
    /**
     * @var int 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
     */
    protected $length = 16;
    
    /**
     * 字符串加密
     * @param string $code - 明文
     * @param int $term - 有效时间（可选，默认为：0即永久有效）
     * @param string $key - 密钥（可选，默认为：kelove_encrypt_key）
     * @return string - 输出密文
     */
    public function encrypt(string $code, int $term = 0, string $key = 'kelove_encrypt_key'): string
    {
        $this->time = $term;
        $this->key = $key;
        // 获取密钥a和密钥b
        $keys = $this->setKeys();
        // 密匙c用于变化生成的密文
        $keys['c'] = substr(md5(microtime()), -$this->length);
        // 参与运算的密匙
        $encryptKey = $keys['a'] . md5($keys['a'] . $keys['c']);
        $keyLength = strlen($encryptKey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
        $code = sprintf('%010d', $term ? $term + time() : 0) . substr(md5($code . $keys['b']), 0, 16) . $code;
        $codeLength = strlen($code);
        // 调用核心加密/解密算法
        $result = $this->core($encryptKey, $keyLength, $code, $codeLength);
        $search = ['%', '=', '+', '/', '&', '#', '?', ' '];
        $replace = ['%25', '', '%2B', '%2F', '%26', '%23', '%3F', '%20'];
        return $keys['c'] . str_replace($search, $replace, base64_encode($result));
    }
    
    /**
     * 字符串解密
     * @param string $code - 密文
     * @param string $key - 密钥（加密时使用的密钥）
     * @return string - 输出明文
     */
    public function decrypt(string $code, string $key = 'kelove_encrypt_key'): string
    {
        $this->key = $key;
        $search = ['%25', '', '%2B', '%2F', '%26', '%23', '%3F', '%20'];
        $replace = ['%', '=', '+', '/', '&', '#', '?', ' '];
        $code = str_replace($search, $replace, $code);
        $keys = $this->setKeys();
        $keys['c'] = substr($code, 0, $this->length);
        // 参与运算的密匙
        $encryptKey = $keys['a'] . md5($keys['a'] . $keys['c']);
        $keyLength = strlen($encryptKey);
        // 如果是解码的话，会从第KEY_LENGTH位开始，因为密文前KEY_LENGTH位保存 动态密匙，以保证解密正确
        $code = base64_decode(substr($code, $this->length));
        $codeLength = strlen($code);
        // 调用核心加密/解密算法
        $result = $this->core($encryptKey, $keyLength, $code, $codeLength);
        // substr($result, 0, 10) == 0 验证数据有效性
        // substr($result, 0, 10) - time() > 0 验证数据有效性
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
        // 验证数据有效性，请看未加密明文的格式
        if (
            (substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0)
            && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keys['b']), 0, 16)
        ) {
            $result = substr($result, 26);
            return $result;
        }
        return '';
    }
    
    /**
     * 密钥初始设置
     * @return array - 密钥数组
     */
    protected function setKeys(): array
    {
        $key = $this->key;
        $keys = [];
        $key = md5($key);
        /* 密钥a参与解密 */
        $keys['a'] = md5(substr($key, 0, 16));
        /* 密匙b用来做数据完整性验证 */
        $keys['b'] = md5(substr($key, 16, 16));
        return $keys;
    }
    
    /**
     * 加密/解密核心部分
     * @param string $encryptKey - 运算密钥
     * @param int $keyLength - 密钥长度
     * @param string $code - 明文/密文
     * @param int $codeLength - 明文/密文长度
     * @return string - 返回核心加密/解密结果
     */
    protected function core(string $encryptKey, int $keyLength, string $code, int $codeLength): string
    {
        $result = '';
        $box = range(0, 255);
        $rndKey = [];
        // 产生密匙簿
        for ($i = 0; $i <= 255; $i++) {
            $rndKey[$i] = ord($encryptKey[$i % $keyLength]);
        }
        // 用固定的算法，打乱密匙簿，增加随机性
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndKey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 核心加解密部分
        for ($a = $j = $i = 0; $i < $codeLength; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($code[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        return $result;
    }
}
