<?php
/**
 *  ==================================================================
 *        文 件 名: Upload.php
 *        概    要: 文件上传及图像处理
 *        作    者: IT小强
 *        创建时间: 2019-01-02 15:29:34
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use kelove\traits\SingleModelTrait;
use think\Image;

/**
 * 文件上传及图像处理
 * Class Upload
 * @package kelove\util
 */
class Upload
{
    use SingleModelTrait;
    
    /**
     * 图片转Base64
     * @param string $url - 图片路径
     * @param string $extension - 扩展名（为空自动获取）
     * @return bool|string
     */
    public function imgTurnBase64(string $url, string $extension = '')
    {
        try {
            $extension = empty($extension) ? pathinfo($url, PATHINFO_EXTENSION) : $extension;
            $file = file_get_contents($url);
        } catch (\Exception $exception) {
            $file = '';
        }
        if (empty($file)) {
            return false;
        }
        $file = 'data:image/' . $extension . ';base64,' . base64_encode($file);
        return $file;
    }
    
    /**
     * 保存Base64图像
     * @param string $base64Img - 原始Base64图像
     * @param string $savePath - 保存路径
     * @param string $saveName - 保存文件名
     * @return bool
     */
    public function saveBase64Img(string $base64Img, string $savePath, string $saveName): bool
    {
        if (empty($base64Img)) {
            $this->message = '请选择要上传的图片';
            return false;
        }
        $base64Img = urldecode($base64Img);
        if (!preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Img, $result)) {
            $this->message = '图片参数错误';
            return false;
        }
        $search = $result[1];
        $extension = '.' . $result[2];
        /* 解码 base64 */
        $img = base64_decode(str_replace($search, '', $base64Img));
        return $this->saveImg($img, $savePath, $saveName, $extension);
    }
    
    /**
     * 抓取远程图片
     * @param string $url - 图片地址
     * @param string $savePath - 保存路径
     * @param string $saveName - 保存文件名
     * @param array $config - 上传配置['size'=>1023,'ext'=>'png,jpg']
     * @return bool|string
     */
    public function getRemoteFile(string $url, string $savePath = '', string $saveName = '', $config = [])
    {
        $imgUrl = str_replace("&amp;", "&", htmlspecialchars($url));
        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            $this->message = '远程图片路径错误';
            return false;
        }
        
        preg_match('/(^https*:\/\/[^:\/]+)/', $imgUrl, $matches);
        $host_with_protocol = count($matches) > 1 ? $matches[1] : '';
        
        // 判断是否是合法 url
        if (!filter_var($host_with_protocol, FILTER_VALIDATE_URL)) {
            $this->message = '远程图片路径不合法';
            return false;
        }
        
        preg_match('/^https*:\/\/(.+)/', $host_with_protocol, $matches);
        $host_without_protocol = count($matches) > 1 ? $matches[1] : '';
        
        // 此时提取出来的可能是 ip 也有可能是域名，先获取 ip
        $ip = gethostbyname($host_without_protocol);
        
        // 判断是否是私有 ip
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            $this->message = '远程图片IP不正确';
            return false;
        }
        
        //获取请求头并检测死链
        $heads = get_headers($imgUrl, 1);
        
        if (!(stristr($heads[0], '200') && stristr($heads[0], 'OK'))) {
            $this->message = '远程图片路径错误';
            return false;
        }
        //格式验证(扩展名验证和Content-Type验证)
        $fileType = str_replace('.', '', strtolower(strrchr($imgUrl, '.')));
        // ① 验证文件格式
        $uploadFileType = get_sub_value('ext', $config, '');
        if (!empty($uploadFileType)) {
            $uploadFileType = explode(',', $uploadFileType);
            if (!in_array($fileType, $uploadFileType)) {
                $this->message = '文件扩展名错误';
                return false;
            }
        }
        
        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(['http' => ['follow_location' => false]]);
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();
        ob_end_clean();
        $size = strlen($img);
        // ② 验证文件大小
        $uploadFileSize = intval(get_sub_value('size', $config, 0));
        if ($uploadFileSize > 0) {
            $uploadFileSize = $uploadFileSize * 1024;
            if ($size > $uploadFileSize) {
                $this->message = '文件大小超出限制';
                return false;
            }
        }
        if (empty($savePath) || empty($saveName)) {
            return $img;
        }
        return $this->saveImg($img, $savePath, $saveName, $fileType);
    }
    
    /**
     * 图像裁剪
     * @param $tempDir - 原图目录
     * @param $saveDir - 保存目录
     * @param $saveName - 文件名
     * @param $width - 宽
     * @param $height - 高
     * @return bool
     */
    public function thumb(string $tempDir, string $saveDir, string $saveName, int $width = 100, int $height = 100): bool
    {
        if (!is_dir($saveDir)) {
            if (!mkdir($saveDir, 0700, true)) {
                $this->message = '创建文件保存目录失败';
                return false;
            }
        }
        $thumb = Image::open($tempDir . $saveName)
            ->thumb($width, $height, Image::THUMB_SCALING)
            ->save($saveDir . $saveName);
        if (!$thumb) {
            $this->message = '图像裁剪失败';
            return false;
        }
        $this->message = '图像裁剪成功';
        return true;
    }
    
    /**
     * 图片保存到本地磁盘
     * @param $content - 图片内容
     * @param $savePath - 保存路径
     * @param $saveName - 文件名
     * @param $extension - 文件扩展名
     * @return bool
     */
    private function saveImg(string $content, string $savePath, string $saveName, string $extension): bool
    {
        /* 创建目录 */
        if (!is_dir($savePath)) {
            if (!mkdir($savePath, 0700, true)) {
                $this->message = '创建文件目录失败';
                return false;
            }
        }
        /* 保存图片 */
        $input = file_put_contents($savePath . $saveName . $extension, $content);
        if (!$input) {
            $this->message = '图片保存失败';
            return false;
        }
        $this->message = [$savePath, $saveName, $extension];
        return true;
    }
}
