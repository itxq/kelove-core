<?php
/**
 *  ==================================================================
 *        文 件 名: Curl.php
 *        概    要: Curl 封装
 *        作    者: IT小强
 *        创建时间: 2019-01-02 15:36:11
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use GuzzleHttp\Client;
use kelove\traits\SingleModelTrait;

/**
 * Curl 封装
 * Class Curl
 * @package kelove\util
 */
class Curl
{
    use SingleModelTrait;
    
    /**
     * @var \GuzzleHttp\Client
     */
    protected $curl;
    
    /**
     * 初始化加载
     */
    protected function initialize(): void
    {
        $this->curl = new Client($this->config);
    }
    
    /**
     * CURL 远程下载文件
     * @param string $url - 远程文件路径
     * @param string $path - 本地保存路径
     * @param string $ext - 指定文件扩展名
     * @return bool|string - 成功返回下载文件的保存路径，失败返回false
     */
    public function curlDownload(string $url, string $path = './curl-download', string $ext = '')
    {
        set_time_limit(0);
        if (empty($ext)) {
            $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        }
        $path = $path . '.' . $ext;
        $fpOutput = fopen($path, 'wb');
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FILE, $fpOutput);
        $re = curl_exec($curl);
        if ($re === false) {
            $statusCode = 404;
        } else {
            try {
                $statusCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            } catch (\Exception $exception) {
                $statusCode = 404;
            }
        }
        curl_close($curl);
        $path = realpath($path);
        if ($statusCode === 200 || $statusCode === 304) {    // 状态值正常表示下载成功
            return $path;
        }
        if ($path) { //下载失败删除临时文件
            @unlink($path);
        }
        return false;
    }
}
