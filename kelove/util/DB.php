<?php
/**
 *  ==================================================================
 *        文 件 名: DB.php
 *        概    要: 数据库备份还原
 *        作    者: IT小强
 *        创建时间: 2019-01-02 16:35:19
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use kelove\traits\SingleModelTrait;

/**
 * 数据库备份还原
 * Class DB
 * @package kelove\util
 */
class DB
{
    use SingleModelTrait;
    
    /**
     * @var \think\facade\Db
     */
    protected $db;
    
    /**
     * @var resource - 文件指针
     */
    private $fp;
    
    /**
     * @var array - 备份文件信息
     */
    private $file = [
        // name - 文件名
        'name' => '',
        // part - 卷号
        'part' => 1
    ];
    
    /**
     * @var integer - 当前打开文件大小
     */
    private $size = 0;
    
    /**
     * @var integer -备份配置
     */
    protected $config = [
        // 数据库备份路径
        'path'     => '',
        // 分卷大小
        'part'     => '20971520',
        // 是否压缩
        'compress' => false,
        // 压缩等级
        'level'    => 1,
        // 备份文件信息
        'file'     => [
            // name - 文件名
            'name' => '',
            // part - 卷号
            'part' => 1
        ],
    ];
    
    /**
     * 初始化加载
     * @param array $config - 配置信息
     */
    protected function initialize(array $config = []): void {
        $this->file = get_sub_value('file', $config, []);
        $this->db = \think\facade\Db::connect();
    }
    
    /**
     * 写入初始数据
     * @return int|bool
     */
    public function create() {
        $sql = "-- -----------------------------\n";
        $sql .= "-- MySQL Data Transfer\n";
        $sql .= "--\n";
        $sql .= "-- Host     : " . config('database.hostname') . "\n";
        $sql .= "-- Port     : " . config('database.hostport') . "\n";
        $sql .= "-- Database : " . config('database.database') . "\n";
        $sql .= "--\n";
        $sql .= "-- Part : #{$this->file['part']}\n";
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
        $sql .= "-- -----------------------------\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        return $this->write($sql);
    }
    
    /**
     * 备份多表
     * @param array $tables
     * @param int $start
     * @return bool
     */
    public function backupAll(array $tables, int $start = 0): bool {
        // 备份指定表
        foreach ($tables as $table) {
            $start = $this->backup($table, $start);
            while (0 !== $start) {
                if (false === $start) { // 出错
                    return false;
                }
                $start = $this->backup($table, $start[0]);
            }
        }
        $sql = "\n\nSET FOREIGN_KEY_CHECKS = 1;\n\n";
        return $this->write($sql) ? true : false;
    }
    
    /**
     * 循环导入多个数据
     * @param array $infoList - 多个数据文件列表
     * @return bool
     */
    public function importAll(array $infoList): bool {
        foreach ($infoList as $k => $v) {
            $this->file[1] = $v['path'];
            $this->config['compress'] = isset($v['compress']) ? $v['compress'] : false;
            $_start = isset($v['start']) ? intval($v['start']) : 0;
            $start = $this->import($_start);
            // 循环导入数据
            while (0 !== $start) {
                if (false === $start) { // 出错
                    return false;
                }
                $start = $this->import($start[0]);
            }
        }
        return true;
    }
    
    /**
     * 导出
     * @param array $tables 表名
     * @param string $path 导出路径
     * @param string $prefix 表前缀
     * @param bool $export_data 是否导出数据
     * @return bool
     */
    public function export(array $tables = [], string $path = '', string $prefix = '', bool $export_data = true) {
        $datetime = date('Y-m-d H:i:s', time());
        $sql = "-- -----------------------------\n";
        $sql .= "-- 导出时间 `{$datetime}`\n";
        $sql .= "-- -----------------------------\n";
        
        if (!empty($tables)) {
            foreach ($tables as $table) {
                $sql .= $this->getSql($prefix . $table, $export_data);
            }
            
            // 写入文件
            if (file_put_contents($path, $sql)) {
                return true;
            };
        }
        return false;
    }
    
    /**
     * 导出卸载文件
     * @param array $tables 表名
     * @param string $path 导出路径
     * @param string $prefix 表前缀
     * @return bool
     */
    public function exportUninstall(array $tables = [], string $path = '', string $prefix = ''): bool {
        $datetime = date('Y-m-d H:i:s', time());
        $sql = "-- -----------------------------\n";
        $sql .= "-- 导出时间 `{$datetime}`\n";
        $sql .= "-- -----------------------------\n";
        
        if (!empty($tables)) {
            foreach ($tables as $table) {
                $sql .= "DROP TABLE IF EXISTS `{$prefix}{$table}`;\n";
            }
            
            // 写入文件
            if (file_put_contents($path, $sql)) {
                return true;
            };
        }
        return false;
    }
    
    /**
     * 获取表结构和数据
     * @param string $table 表名
     * @param bool $export_data 是否导出数据
     * @param int $start 起始行数
     * @return string
     */
    public function getSql(string $table, bool $export_data = false, int $start = 0): string {
        $sql = "";
        if ($this->db->query("SHOW TABLES LIKE '%{$table}%'")) {
            // 表结构
            if ($start == 0) {
                $result = $this->db->query("SHOW CREATE TABLE `{$table}`");
                $sql .= "\n-- -----------------------------\n";
                $sql .= "-- 表结构 `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= trim($result[0]['Create Table']) . ";\n\n";
            }
            
            // 表数据
            if ($export_data) {
                $sql .= "-- -----------------------------\n";
                $sql .= "-- 表数据 `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                
                // 数据总数
                $result = $this->db->query("SELECT COUNT(*) AS count FROM `{$table}`");
                $count = $result['0']['count'];
                
                // 备份数据记录
                $result = $this->db->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
                foreach ($result as $row) {
                    $row = array_map('addslashes', $row);
                    $sql .= "INSERT INTO `{$table}` VALUES ('" . str_replace(array("\r", "\n"), array('\r', '\n'), implode("', '", $row)) . "');\n";
                }
                
                // 还有更多数据
                if ($count > $start + 1000) {
                    $sql .= $this->getSql($table, $export_data, $start + 1000);
                }
            }
        }
        
        return $sql;
    }
    
    /**
     * 备份表结构
     * @param string $table 表名
     * @param int $start 起始行数
     * @return array|bool|int  false - 备份失败
     */
    protected function backup(string $table, int $start = 0) {
        // 备份表结构
        if (0 == $start) {
            $result = $this->db->query("SHOW CREATE TABLE `{$table}`");
            $result = array_map('array_change_key_case', $result);
            
            $sql = "\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "-- Table structure for `{$table}`\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= trim($result[0]['create table']) . ";\n\n";
            if (false === $this->write($sql)) {
                return false;
            }
        }
        
        // 数据总数
        $result = $this->db->query("SELECT COUNT(*) AS count FROM `{$table}`");
        $count = $result['0']['count'];
        
        //备份表数据
        if ($count) {
            // 写入数据注释
            if (0 == $start) {
                $sql = "-- -----------------------------\n";
                $sql .= "-- Records of `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $this->write($sql);
            }
            
            // 备份数据记录
            $result = $this->db->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
            foreach ($result as $k => $row) {
                $sql = "INSERT INTO `{$table}` VALUES (" . str_replace(["\r", "\n"], ['\r', '\n'], $this->getInsertValue($row)) . ");\n";
                if (false === $this->write($sql)) {
                    return false;
                }
            }
            
            //还有更多数据
            if ($count > $start + 1000) {
                return array($start + 1000, $count);
            }
        }
        
        // 备份下一表
        return 0;
    }
    
    /**
     * 数据格式处理
     * @param $row
     * @return string
     */
    protected function getInsertValue(array $row): string {
        foreach ($row as $k => $v) {
            if (is_numeric($v) && (substr($v, 0, 1) != 0 || strlen($v) == 1)) {
                $row[$k] = $v;
            } else if (is_null($v)) {
                $row[$k] = 'null';
            } else {
                $row[$k] = "'" . addslashes($v) . "'";
            }
        }
        return implode(',', $row);
    }
    
    /**
     * 导入数据
     * @param int $start 起始位置
     * @return array|bool|int
     */
    protected function import(int $start = 0) {
        if ($this->config['compress']) {
            $gz = gzopen($this->file[1], 'r');
            $size = 0;
        } else {
            $size = filesize($this->file[1]);
            $gz = fopen($this->file[1], 'r');
        }
        
        $sql = '';
        if ($start) {
            $this->config['compress'] ? gzseek($gz, $start) : fseek($gz, $start);
        }
        
        for ($i = 0; $i < 1000; $i++) {
            $sql .= $this->config['compress'] ? gzgets($gz) : fgets($gz);
            if (preg_match('/.*;$/', trim($sql))) {
                if (false !== $this->db->execute($sql)) {
                    $start += strlen($sql);
                } else {
                    return false;
                }
                $sql = '';
            } elseif ($this->config['compress'] ? gzeof($gz) : feof($gz)) {
                return 0;
            }
        }
        
        return [$start, $size];
    }
    
    /**
     * 打开一个卷，用于写入数据
     * @param int $size 写入数据的大小
     */
    protected function open(int $size = 0): void {
        if ($this->fp) {
            $this->size += $size;
            if ($this->size > $this->config['part']) {
                $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
                session('backup_file', $this->file);
                $this->create();
            }
        } else {
            $backup_path = $this->config['path'];
            $filename = "{$backup_path}{$this->file['name']}-{$this->file['part']}.sql";
            if ($this->config['compress']) {
                $filename = "{$filename}.gz";
                $this->fp = @gzopen($filename, "a{$this->config['level']}");
            } else {
                $this->fp = @fopen($filename, 'a');
            }
            $this->size = filesize($filename) + $size;
        }
    }
    
    /**
     * 写入SQL语句
     * @param string $sql 要写入的SQL语句
     * @return int|bool
     */
    protected function write(string $sql = '') {
        $size = strlen($sql);
        
        // 由于压缩原因，无法计算出压缩后的长度，这里假设压缩率为50%，
        // 一般情况压缩率都会高于50%；
        $size = $this->config['compress'] ? $size / 2 : $size;
        
        $this->open($size);
        return $this->config['compress'] ? @gzwrite($this->fp, $sql) : @fwrite($this->fp, $sql);
    }
    
    /**
     * 析构方法，用于关闭文件资源
     */
    public function __destruct() {
        $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
    }
}