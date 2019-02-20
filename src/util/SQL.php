<?php
/**
 *  ==================================================================
 *        文 件 名: SQL.php
 *        概    要: SQL文件操作类
 *        作    者: IT小强
 *        创建时间: 2018-12-29 10:18:30
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use kelove\traits\SingleModelTrait;

/**
 * SQL文件操作类 - 读取Sql文件并返回可执行的sql语句
 * Class SQL
 * @package kelove\util
 */
class SQL
{
    use SingleModelTrait;
    
    /**
     * 从sql文件获取纯sql语句
     * @param  string $sqlFile sql文件路径
     * @param  array $replace 替换前缀，如：['my_' => 'me_']，表示将表前缀"my_"替换成"me_"
     *         这种前缀替换方法不一定准确，比如正常内容内有跟前缀相同的字符，也会被替换
     * @return array
     */
    public function getSqlFromFile(string $sqlFile = '', array $replace = []): array
    {
        if (!is_file($sqlFile)) {
            return [];
        }
        
        // 读取sql文件内容
        $handle = file_get_contents($sqlFile);
        
        // 分割语句
        $handle = $this->parseSql($handle, $replace);
        
        return $handle;
    }
    
    /**
     * 分割sql语句
     * @param string $content sql内容
     * @param array $replace 替换前缀，如：['my_' => 'me_']，表示将表前缀my_替换成me_
     * @return array 除去注释之后的sql语句数组
     */
    public function parseSql(string $content = '', array $replace = []): array
    {
        if (empty($content)) {
            return [];
        }
        
        // 被替换的内容
        $from = array_keys($replace);
        // 要替换的内容
        $to = array_values($replace);
        
        
        // 纯sql内容
        $pureSql = [];
        
        // 多行注释标记
        $comment = false;
        
        // 替换
        $content = str_replace($from, $to, $content);
        
        // 按行分割，兼容多个平台
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = explode("\n", trim($content));
        
        // 循环处理每一行
        foreach ($content as $key => $line) {
            // 跳过空行
            if ($line == '') {
                continue;
            }
            
            // 跳过以#或者--开头的单行注释
            if (preg_match("/^(#|--)/", $line)) {
                continue;
            }
            
            // 跳过以/**/包裹起来的单行注释
            if (preg_match("/^\/\*(.*?)\*\//", $line)) {
                continue;
            }
            
            // 多行注释开始
            if (substr($line, 0, 2) == '/*') {
                $comment = true;
                continue;
            }
            
            // 多行注释结束
            if (substr($line, -2) == '*/') {
                $comment = false;
                continue;
            }
            
            // 多行注释没有结束，继续跳过
            if ($comment) {
                continue;
            }
            
            // sql语句
            array_push($pureSql, $line);
        }
        // 以数组形式返回sql语句
        $pureSql = implode($pureSql, "\n");
        $pureSql = explode(";\n", $pureSql);
        return $pureSql;
    }
}
