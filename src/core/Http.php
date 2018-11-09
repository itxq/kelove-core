<?php
/**
 *  ==================================================================
 *        文 件 名: Http.php
 *        概    要: Http处理类
 *        作    者: IT小强
 *        创建时间: 2018-11-09 15:42
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\kelove\core;

use itxq\kelove\SingleModel;

/**
 * Http处理类
 * Class Http
 * @package itxq\kelove\core
 */
class Http extends SingleModel
{
    /**
     * 发送GET请求
     * @param string $url ,请求的URL
     * @param bool $ssl - 是否为ssl
     */
    public function requestGet($url, $ssl = true) {
        $this->curlRequest($url, null, $ssl);
    }
    
    /**
     * 发送POST请求
     * @param string $url ,请求的URL
     * @param bool $ssl - 是否为ssl
     * @param array $data
     */
    public function requestPost($url, $data = [], $ssl = true) {
        $this->curlRequest($url, $data, $ssl);
    }
    
    /**
     * Curl发送请求
     * @param string $url ,请求的URL
     * @param null|array $data ,GET请求此项为null,POST请求此项为POST数据
     * @param bool $ssl - 是否为ssl
     * @return mixed
     */
    public function curlRequest($url, $data = null, $ssl = true) {
        /* 利用 Curl 完成 GET/POST 请求 */
        $curl = curl_init();
        /* curl 配置项 */
        /* 请求 URL */
        curl_setopt($curl, CURLOPT_URL, $url);
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '
Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        /* user_agent，请求代理信息 */
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        /* referer头，请求来源 */
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        /* ssl相关选项 */
        if ($ssl) {
            /* 禁用后 Curl 将终止从服务端进行验证 */
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            /* 检查服务器SSL证书中是否存在一个公用名(common name) */
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }
        /* post相关选项 */
        if ($data !== null) {
            /* 是否为POST请求 */
            curl_setopt($curl, CURLOPT_POST, true);
            /* 处理请求数据 */
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        /* 是否处理响应头 ->否 */
        curl_setopt($curl, CURLOPT_HEADER, false);
        /* curl_exec()是否返回响应结果 ->是 */
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        /* 发送请求 */
        $response = curl_exec($curl);
        if (false === $response) {
            $this->message = curl_error($curl);
        }
        curl_close($curl);
        return $response;
    }
    
    /**
     * CURL 远程下载文件
     * @param $url - 远程文件路径
     * @param string $path - 本地保存路径
     * @param string $ext - 指定文件控制名
     * @return bool|string - 成功返回下载文件的保存路径，失败返回false
     */
    public function curlDownload($url, $path = './curl-download', $ext = '') {
        set_time_limit(0);
        if (empty($ext)) {
            $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        }
        $path = $path . '.' . $ext;
        $fpOutput = fopen($path, 'w');
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FILE, $fpOutput);
        $re = curl_exec($curl);
        if ($re === false) {
            $statusCode = 404;
        } else {
            try {
                $statusCode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
            } catch (\Exception $exception) {
                $statusCode = 404;
            }
        }
        curl_close($curl);
        $path = realpath($path);
        if ($statusCode == 200 || $statusCode == 304) {    // 状态值正常表示下载成功
            return $path;
        }
        if ($path) { //下载失败删除临时文件
            @unlink($path);
        }
        return false;
    }
}