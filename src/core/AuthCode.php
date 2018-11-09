<?php
/**
 *  ==================================================================
 *        文 件 名: AuthCode.php
 *        概    要: 加密、解密程序
 *        作    者: IT小强
 *        创建时间: 2018-11/09 15:28
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\kelove\core;

use itxq\kelove\SingleModel;

/**
 * 加密、解密程序
 * Class AuthCode
 * @package itxq\kelove\core
 */
class AuthCode extends SingleModel
{
    /**
     * 动态密钥
     */
    const KEY = 'AuthCodeKey';
    
    /**
     * 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
     */
    const KEY_LENGTH = 16;
    
    /**
     * 密钥期限
     */
    const KEY_TERM = 0;
    
    /**
     * 字符串、数组加密
     * @param $code - 明文(支持传入关联数组或者字符串)
     * @param string $key ，密钥（可选，默认为：AuthCodeKey）
     * @param int $term ，有效时间（可选，默认为：0即永久有效）
     * @return string - 输出密文
     */
    public function encrypt($code, $term = self::KEY_TERM, $key = self::KEY) {
        //如果明文为数组。则先行转为json字符串
        if (is_array($code)) {
            $code = json_encode($code);
        }
        // 获取密钥a和密钥b
        $keys = $this->_setKeys($key);
        // 密匙c用于变化生成的密文
        $keys['c'] = substr(md5(microtime()), -self::KEY_LENGTH);
        // 参与运算的密匙
        $encryptKey = $keys['a'] . md5($keys['a'] . $keys['c']);
        $keyLength = strlen($encryptKey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
        $code = sprintf('%010d', $term ? $term + time() : 0) . substr(md5($code . $keys['b']), 0, 16) . $code;
        $codeLength = strlen($code);
        // 调用核心加密/解密算法
        $result = $this->_core($encryptKey, $keyLength, $code, $codeLength);
        $search = ['%', '=', '+', '/', '&', '#', '?', ' '];
        $replace = ['%25', '', '%2B', '%2F', '%26', '%23', '%3F', '%20'];
        return $keys['c'] . str_replace($search, $replace, base64_encode($result));
    }
    
    /**
     * 字符串、数组解密
     * @param $code - 密文
     * @param string $key - 密钥（加密时使用的密钥）
     * @return string|array|bool 输出明文,(失败返回false)
     */
    public function decrypt($code, $key = self::KEY) {
        $search = ['%25', '', '%2B', '%2F', '%26', '%23', '%3F', '%20'];
        $replace = ['%', '=', '+', '/', '&', '#', '?', ' '];
        $code = str_replace($search, $replace, $code);
        $keys = $this->_setKeys($key);
        $keys['c'] = substr($code, 0, self::KEY_LENGTH);
        // 参与运算的密匙
        $encryptKey = $keys['a'] . md5($keys['a'] . $keys['c']);
        $keyLength = strlen($encryptKey);
        // 如果是解码的话，会从第KEY_LENGTH位开始，因为密文前KEY_LENGTH位保存 动态密匙，以保证解密正确
        $code = base64_decode(substr($code, self::KEY_LENGTH));
        $codeLength = strlen($code);
        // 调用核心加密/解密算法
        $result = $this->_core($encryptKey, $keyLength, $code, $codeLength);
        // substr($result, 0, 10) == 0 验证数据有效性
        // substr($result, 0, 10) - time() > 0 验证数据有效性
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
        // 验证数据有效性，请看未加密明文的格式
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keys['b']), 0, 16)) {
            $result = substr($result, 26);
            $result_json = json_decode($result, true);
            return $result_json ? $result_json : $result;
        } else {
            return false;
        }
    }
    
    /**
     * 密钥初始设置
     * @param null $key ，解密KEY
     * @return array,密钥数组
     */
    protected function _setKeys($key = NULL) {
        $keys = [];
        /* 设置密钥 */
        if ($key == NULL) {
            $key = self::KEY;
        }
        $key = md5($key);
        /* 密钥a参与解密 */
        $keys['a'] = md5(substr($key, 0, 16));
        /* 密匙b用来做数据完整性验证 */
        $keys['b'] = md5(substr($key, 16, 16));
        return $keys;
    }
    
    /**
     * 加密/解密核心部分
     * @param $encryptKey - 运算密钥
     * @param $keyLength - 密钥长度
     * @param $code - 明文/密文
     * @param $codeLength - 明文/密文长度
     * @return string - 返回核心加密/解密结果
     */
    protected function _core($encryptKey, $keyLength, $code, $codeLength) {
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