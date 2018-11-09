<?php
/**
 *  ==================================================================
 *        文 件 名: helper.php
 *        概    要: 助手函数
 *        作    者: IT小强
 *        创建时间: 2018-11-09 15:02
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

if (!function_exists('sort_by_key')) {
    /**
     * 二维数组指定字段排序
     * @param $array - 原始数组
     * @param $keyName - 字段名
     * @param string $type - 排序类型【SORT_ASC|SORT_DESC】
     * @return array - 返回排序之后的数组
     */
    function sort_by_key($array, $keyName, $type = 'SORT_DESC') {
        if (!is_array($array) || count($array) < 1) {
            return [];
        }
        $arrSort = [];
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$keyName], constant($type), $array);
        return $array;
    }
}

if (!function_exists('byte_format')) {
    
    /**
     * 格式化字节大小
     * @param $bytes - 字节数
     * @param string $unit - 转换单位
     * @param int $decimals - 保留小数位数
     * @return string
     */
    function byte_format($bytes, $unit = '', $decimals = 2) {
        $units = ['B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8];
        $value = 0;
        if ($bytes > 0) {
            if (!array_key_exists($unit, $units)) {
                $pow = floor(log($bytes) / log(1024));
                $unit = array_search($pow, $units);
            }
            $value = ($bytes / pow(1024, floor($units[$unit])));
        }
        
        if (!is_numeric($decimals) || $decimals < 0) {
            $decimals = 2;
        }
        return sprintf('%.' . $decimals . 'f ' . $unit, $value);
    }
}

if (!function_exists('get_sub_value')) {
    /**
     * 获取数组、对象下标对应值，不存在时返回指定的默认值
     * @param string|integer $name - 下标（键名）
     * @param array|object $data - 原始数组/对象
     * @param mixed $default - 指定默认值
     * @return mixed
     */
    function get_sub_value($name, $data, $default = '') {
        if (is_object($data)) {
            $value = isset($data->$name) ? $data->$name : $default;
        } else if (is_array($data)) {
            $value = isset($data[$name]) ? $data[$name] : $default;
        } else {
            $value = $default;
        }
        return $value;
    }
}

if (!function_exists('rgb_to_hex')) {
    /**
     * RGB 转 十六进制
     * @param $rgb - RGB颜色的字符串 如：rgb(255,255,255);
     * @return string 十六进制颜色值 如：#FFFFFF
     */
    function rgb_to_hex($rgb) {
        $regexp = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
        preg_match($regexp, $rgb, $match);
        array_shift($match);
        $hexColor = "#";
        $hex = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
        for ($i = 0; $i < 3; $i++) {
            $r = null;
            $c = $match[$i];
            $hexAr = [];
            while ($c > 16) {
                $r = $c % 16;
                $c = ($c / 16) >> 0;
                array_push($hexAr, $hex[$r]);
            }
            array_push($hexAr, $hex[$c]);
            $ret = array_reverse($hexAr);
            $item = implode('', $ret);
            $item = str_pad($item, 2, '0', STR_PAD_LEFT);
            $hexColor .= $item;
        }
        return $hexColor;
    }
}

if (!function_exists('hex_to_rgb')) {
    /**
     * 十六进制 转 RGB
     * @param $hexColor - 十六进制颜色 如：#FFF000;
     * @param $isString - 是否返回字符串格式
     * @return array |string
     */
    function hex_to_rgb($hexColor, $isString = false) {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = ['r' => hexdec($r), 'g' => hexdec($g), 'b' => hexdec($b)];
        }
        if ($isString) {
            return 'rgb(' . $rgb['r'] . ',' . $rgb['g'] . ',' . $rgb['b'] . ')';
        }
        return $rgb;
    }
}

if (!function_exists('underline_to_hump')) {
    
    /**
     * 下划线命名转驼峰命名
     * @param $str - 下划线命名字符串
     * @param $is_first - 是否为大驼峰（即首字母也大写）
     * @return mixed
     */
    function underline_to_hump($str, $is_first = false) {
        $str = preg_replace_callback('/([\-\_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        if ($is_first) {
            $str = ucfirst($str);
        }
        return $str;
    }
}

if (!function_exists('hump_to_underline')) {
    
    /**
     * 驼峰命名转下划线命名
     * @param $str
     * @return mixed
     */
    function hump_to_underline($str) {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        $str = preg_replace('/^\_/', '', $str);
        return $str;
    }
}

if (!function_exists('cm_explode')) {
    
    /**
     * 字符串按分隔符转为数组
     * @param $string - 指定需要分割的字符串
     * @param $delimiter - 分割符
     * @return array
     */
    function cm_explode($string, $delimiter = ',') {
        if (!$string || !is_string($string)) {
            return [];
        }
        if (!strpos($string, $delimiter)) {
            return ['0' => $string];
        }
        $tempArr = explode($delimiter, $string);
        $returnData = [];
        foreach ($tempArr as $k => $v) {
            $returnData[strval($k)] = $v;
        }
        return $returnData;
    }
}

if (!function_exists('cm_round')) {
    /**
     * 生成随机字符串
     * @param int $length - 指定生成字符串的长度
     * @param string $type - 指定生成字符串的类型（all-全部，num-纯数字，letter-纯字母）
     * @return null|string
     */
    function cm_round($length = 4, $type = 'all') {
        $str = '';
        $strUp = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $strLow = 'abcdefghijklmnopqrstuvwxyz';
        $number = '0123456789';
        switch ($type) {
            case 'num':
                $strPol = $number;
                break;
            case 'letter':
                $strPol = $strUp . $strLow;
                break;
            default:
                $strPol = $strUp . $number . $strLow;
        }
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[mt_rand(0, $max)];
        }
        return $str;
    }
}

if (!function_exists('cm_uuid')) {
    
    /**
     * 生成UUID
     * @return string
     */
    function cm_uuid() {
        $charId = md5(uniqid(mt_rand(), true));
        $uuid = substr($charId, 0, 8)
            . substr($charId, 8, 4)
            . substr($charId, 12, 4)
            . substr($charId, 16, 4)
            . substr($charId, 20, 12);
        return strtolower($uuid);
    }
}

if (!function_exists('cm_array_merge')) {
    
    /**
     * 数组合并，可传入多个数组
     * @param $array1 - 第一个参数必须，参数必须为数组
     * @return array
     */
    function cm_array_merge($array1) {
        $returnArray = [];
        $args = func_get_args();
        foreach ($args as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $sk => $sv) {
                    $returnArray[$sk] = $sv;
                }
            }
        }
        return $returnArray;
    }
}

if (!function_exists('format_array')) {
    /**
     * 格式化数组，可以指定数组的键和值
     * @param $array - 原始数组
     * @param $key - 键
     * @param $value - 值
     * @param $delimiter - 分隔符，二级数组元素为字符串时
     * @return array -返回格式化后的数组
     */
    function format_array($array, $key = 'id', $value = 'name', $delimiter = ':') {
        $returnArray = [];
        if (!$array || !is_array($array) || count($array) < 1) {
            return $returnArray;
        }
        foreach ($array as $k => $item) {
            if (empty($key) && empty($value)) {
                $returnArray[$k] = $item;
            } else if (is_string($item) && strpos($item, $delimiter)) {
                $tempArr = explode($delimiter, $item);
                $newKey = $tempArr[0];
                $newValue = $tempArr[1];
                $returnArray[$newKey] = $newValue;
            } else if (isset($item[$key]) && isset($item[$value])) {
                $newKey = $item[$key];
                $newValue = $item[$value];
                $returnArray[$newKey] = $newValue;
            } else {
                break;
            }
        }
        return $returnArray;
    }
}

if (!function_exists('array_serialize_url')) {
    
    /**
     * 数组序列化url字符串
     * @param $array - 数组（键值对）
     * @return string -序列化后的字符串
     */
    function array_serialize_url($array) {
        $str = '';
        if (!is_array($array) || count($array) < 1) {
            return $str;
        }
        foreach ($array as $k => $v) {
            $str .= (empty($str)) ? '?' : '&';
            $str .= $k . '=' . $v;
        }
        return $str;
    }
}

if (!function_exists('curl_download')) {
    
    /**
     * CURL 远程下载文件
     * @param string $url - 远程文件路径
     * @param string $path - 本地保存路径
     * @param string $ext - 指定文件控制名
     * @return bool|string - 成功返回下载文件的保存路径，失败返回false
     */
    function curl_download($url, $path = './curl-download', $ext = '') {
        return \itxq\kelove\core\Http::ins()->curlDownload($url, $path, $ext);
    }
}

if (!function_exists('curl_request')) {
    /**
     * Curl发送请求
     * @param string $url ,请求的URL
     * @param null|array $data ,GET请求此项为null,POST请求此项为POST数据
     * @param bool $ssl - 是否为ssl
     * @return mixed
     */
    function curl_request($url, $data = null, $ssl = true) {
        return \itxq\kelove\core\Http::ins()->curlRequest($url, $data, $ssl);
    }
}

if (!function_exists('ip_lookup')) {
    /**
     * IP地址信息查询
     * @param string $ip - 待查询的IP
     * @param string $driver - 查询驱动（local、taobao）
     * @return array|bool - 查询失败返回false，成功返回地址信息数组
     */
    function ip_lookup($ip, $driver = 'local') {
        return \itxq\kelove\ip\IP::ins(['driver' => $driver], true)->lookup($ip);
    }
}