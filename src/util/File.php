<?php
/**
 *  ==================================================================
 *        文 件 名: File.php
 *        概    要: 文件目录操作类
 *        作    者: IT小强
 *        创建时间: 2018-12-29 11:39:49
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use kelove\traits\SingleModelTrait;

/**
 * 文件目录操作类
 * Class File
 * @package kelove\util
 */
class File
{
    use SingleModelTrait;
    
    /**
     * 创建目录
     * @param $dir -目录名
     * @return boolean true 成功， false 失败
     */
    public function mkDir(string $dir): bool
    {
        $dir = rtrim($dir, '/') . '/';
        if (is_dir($dir)) {
            return true;
        }
        return mkdir($dir, 0777, true);
    }
    
    /**
     * 写文件
     * @param string $filename - 文件名
     * @param string $writeText - 文件内容
     * @param string $openMod - 打开方式
     * @return bool - true 成功false 失败
     */
    public function writeFile($filename, $writeText, $openMod = 'w'): bool
    {
        if (@$fp = fopen($filename, $openMod)) {
            flock($fp, 2);
            fwrite($fp, $writeText);
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 删除目录
     * @param string $dirName -原目录
     * @return boolean true 成功, false 失败
     */
    public function delDir(string $dirName): bool
    {
        if (!file_exists($dirName)) {
            return false;
        }
        
        $dir = opendir($dirName);
        while ($fileName = readdir($dir)) {
            $file = $dirName . '/' . $fileName;
            if ($fileName != '.' && $fileName != '..') {
                if (is_dir($file)) {
                    $this->delDir($file);
                } else {
                    unlink($file);
                }
            }
        }
        closedir($dir);
        return rmdir($dirName);
    }
    
    /**
     * 复制目录
     * @param string $surDir - 原目录
     * @param string $toDir - 目标目录
     * @return boolean true 成功, false 失败
     */
    public function copyDir(string $surDir, string $toDir): bool
    {
        $surDir = rtrim($surDir, '/') . '/';
        $toDir = rtrim($toDir, '/') . '/';
        if (!file_exists($surDir)) {
            return false;
        }
        
        if (!file_exists($toDir)) {
            $this->mkDir($toDir);
        }
        $file = opendir($surDir);
        while ($fileName = readdir($file)) {
            $file1 = $surDir . '/' . $fileName;
            $file2 = $toDir . '/' . $fileName;
            if ($fileName != '.' && $fileName != '..') {
                if (is_dir($file1)) {
                    $this->copyDir($file1, $file2);
                } else {
                    copy($file1, $file2);
                }
            }
        }
        closedir($file);
        return true;
    }
    
    /**
     * 列出目录
     * @param string $dir -目录名
     * @param bool $isDel - 是否删除当前目录和上级目录（. or ..）
     * @return array - 目录数组。列出文件夹下内容，返回数组 $dirArray['dir']:存文件夹；$dirArray['file']：存文件
     */
    public function getDirs(string $dir, bool $isDel = true): array
    {
        $dir = rtrim($dir, '/') . '/';
        $dirArray = [];
        if (false != ($handle = opendir($dir))) {
            $i = 0;
            $j = 0;
            while (false !== ($file = readdir($handle))) {
                if (is_dir($dir . $file)) { //判断是否文件夹
                    if ($isDel == false) {
                        $dirArray ['dir'] [$i] = $file;
                        $i++;
                    } else {
                        if ($file != '.' && $file != '..') {
                            $dirArray ['dir'] [$i] = $file;
                            $i++;
                        }
                    }
                } else {
                    $dirArray ['file'] [$j] = $file;
                    $j++;
                }
            }
            closedir($handle);
        }
        return $dirArray;
    }
    
    /**
     * 递归获取指定目录下的所有文件
     * @param string $dir - 指定目录路径
     * @param bool $isList - 是否返回一维数组（默认否，即返回多维数组）
     * @param array $list - 无需赋值
     * @return array
     */
    public function getFiles(string $dir, bool $isList = false, array $list = []): array
    {
        $data = [];
        $dir = realpath($dir);
        if (!is_dir($dir)) {
            return [];
        }
        $dh = opendir($dir);//打开目录
        while (($d = readdir($dh)) !== false) {
            if ($d === '.' || $d === '..') {
                continue;
            }
            $fullPath = $dir . DIRECTORY_SEPARATOR . $d;
            if (is_dir($fullPath)) {
                $child = $this->getFiles($fullPath, $isList, $list);
                $data[$d] = $child;
                $list = $child;
            } else {
                $data[$d] = $fullPath;
                $list[$fullPath] = $fullPath;
            }
        }
        closedir($dh);
        return $isList === true ? $list : $data;
    }
    
    /**
     * 统计文件夹大小
     * @param string $dir - 目录名
     * @return number 文件夹大小(单位 B)
     */
    public function getSize(string $dir)
    {
        $dirList = opendir($dir);
        $dirSize = 0;
        while (false !== ($folderOrFile = readdir($dirList))) {
            if ($folderOrFile != '.' && $folderOrFile != '..') {
                if (is_dir("$dir/$folderOrFile")) {
                    $dirSize += $this->getSize("$dir/$folderOrFile");
                } else {
                    $dirSize += filesize("$dir/$folderOrFile");
                }
            }
        }
        closedir($dirList);
        return $dirSize;
    }
    
    /**
     * 检测是否为空文件夹
     * @param string $dir - 目录名
     * @return boolean - true 空， false 不为空
     */
    public function emptyDir(string $dir): bool
    {
        return (($files = @scandir($dir)) && count($files) <= 2);
    }
    
    /**
     * 解压zip格式的压缩文件
     * @param string $zipFile - zip文件路径
     * @param string $unzipDir - 解压路径
     * @param bool $mkNameDir - 是否以压缩文件名命起始目录
     * @param bool $overWrite - 是否覆盖已有文件(true-覆盖|false-不覆盖|string-以该字符串命名目录)
     * @return bool
     */
    public function unzip(
        string $zipFile,
        string $unzipDir = './',
        bool $mkNameDir = false,
        bool $overWrite = false
    ): bool {
        // 获取压缩文件全路径
        $zipFile = realpath($zipFile);
        if (!is_file($zipFile)) {
            $this->message = '压缩文件不存在';
            return false;
        }
        // 打开压缩文件
        $zip = zip_open($zipFile);
        if (!$zip) {
            // 打开失败
            $this->message = '打开压缩文件失败';
            return false;
        }
        // 如果不存在 创建目标解压目录
        if (!is_dir($unzipDir) && !mkdir($unzipDir, 0755, true)) {
            $this->message = '创建目录失败';
            return false;
        }
        // 获取压缩路径
        $unzipDir = realpath($unzipDir) . DIRECTORY_SEPARATOR;
        if ($mkNameDir !== false) {
            // 创建起始目录
            if (is_string($mkNameDir)) {
                $zipName = $mkNameDir;
            } else {
                // 创建和压缩文件名相同的目录名作为起始目录
                $start = intval(strrpos($zipFile, DIRECTORY_SEPARATOR));
                $end = strrpos($zipFile, '.') - 1;
                $zipName = substr($zipFile, $start + 1, $end - $start);
            }
            $unzipDir = $unzipDir . $zipName . DIRECTORY_SEPARATOR;
        }
        
        // 如果不存在 创建目标解压目录
        if (!is_dir($unzipDir) && !mkdir($unzipDir, 0755, true)) {
            $this->message = '创建目录失败';
            return false;
        }
        // 对每个文件进行解压
        while ($zipEntry = zip_read($zip)) {
            $search = ['/', '\\'];
            $replace = [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR];
            $zipEntryName = str_replace($search, $replace, zip_entry_name($zipEntry));
            // 判断是文件还是目录
            $posLastSlash = strrpos($zipEntryName, DIRECTORY_SEPARATOR);
            if ($posLastSlash !== false) {
                // 如果是目录则拼装完整路径
                $thisPath = $unzipDir . substr($zipEntryName, 0, $posLastSlash) . DIRECTORY_SEPARATOR;
                // 目录不存在时，创建目录
                if (!is_dir($thisPath) && !mkdir($thisPath, 0755, true)) {
                    $this->message = '创建目录失败';
                    return false;
                }
            }
            // 打开包
            if (zip_entry_open($zip, $zipEntry, 'r')) {
                // 文件名保存在磁盘上
                $fileName = $unzipDir . $zipEntryName;
                // 检查文件是否需要重写
                if (!is_file($fileName) || ($overWrite === true)) {
                    // 读取压缩文件的内容
                    $readeContent = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
                    @file_put_contents($fileName, $readeContent);
                    // 设置权限
                    @chmod($fileName, 0777);
                }
                // 关闭入口
                zip_entry_close($zipEntry);
            }
        }
        // 关闭压缩包
        zip_close($zip);
        return true;
    }
}
