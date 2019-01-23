<?php
/**
 *  ==================================================================
 *        文 件 名: MySQL.php
 *        概    要: MySQL数据库备份还原
 *        作    者: IT小强
 *        创建时间: 2019-01-02 16:35:19
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use kelove\traits\SingleModelTrait;
use think\facade\Config;

/**
 * MySQL数据库备份还原
 * Class MySQL
 * @package kelove\util
 */
class MySQL
{
    use SingleModelTrait;
    
    /**
     * @var string - 换行符
     */
    protected $eol = PHP_EOL;
    
    /**
     * @var \think\facade\Db - 数据库操作类
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
        'name' => 'sql_backup',
        // part - 卷号
        'part' => 1
    ];
    
    /**
     * @var integer - 当前打开文件大小
     */
    private $size = 0;
    
    /**
     * @var integer - 默认备份配置
     */
    protected $defaultConfig = [
        // 数据库备份路径
        'path'     => __DIR__ . '/',
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
     * @var array - 数据库配置信息
     */
    protected $database = [];
    
    /**
     * 初始化加载
     * @param array $config - 配置信息
     */
    protected function initialize(array $config = []): void {
        $this->file = get_sub_value('file', $config, []);
        $this->database = array_merge((array)Config::pull('database'), get_sub_value('database', $config, []));
        $this->db = \think\facade\Db::connect($this->database);
    }
    
    /**
     * 写入初始数据
     * @return int|bool
     */
    public function create() {
        $sql = "-- -----------------------------$this->eol";
        $sql .= "-- MySQL Data Transfer$this->eol";
        $sql .= "--$this->eol";
        $sql .= "-- Host     : " . get_sub_value('hostname', $this->database, '127.0.0.1') . "$this->eol";
        $sql .= "-- Port     : " . get_sub_value('hostport', $this->database, '3306') . "$this->eol";
        $sql .= "-- Database : " . get_sub_value('database', $this->database, '') . "$this->eol";
        $sql .= "--$this->eol";
        $sql .= "-- Part : #{$this->file['part']}$this->eol";
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . "$this->eol";
        $sql .= "-- -----------------------------{$this->eol}{$this->eol}";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;{$this->eol}{$this->eol}";
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
        $sql = "{$this->eol}{$this->eol}SET FOREIGN_KEY_CHECKS = 1;{$this->eol}{$this->eol}";
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
     * @param bool $exportData 是否导出数据
     * @return bool
     */
    public function export(array $tables = [], string $path = '', string $prefix = '', bool $exportData = true) {
        $datetime = date('Y-m-d H:i:s', time());
        $sql = "-- -----------------------------$this->eol";
        $sql .= "-- 导出时间 `{$datetime}`$this->eol";
        $sql .= "-- -----------------------------$this->eol";
        
        if (!empty($tables)) {
            foreach ($tables as $table) {
                $sql .= $this->getSql($prefix . $table, $exportData);
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
        $sql = "-- -----------------------------$this->eol";
        $sql .= "-- 导出时间 `{$datetime}`$this->eol";
        $sql .= "-- -----------------------------$this->eol";
        
        if (!empty($tables)) {
            foreach ($tables as $table) {
                $sql .= "DROP TABLE IF EXISTS `{$prefix}{$table}`;$this->eol";
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
     * @param bool $exportData 是否导出数据
     * @param int $start 起始行数
     * @return string
     */
    public function getSql(string $table, bool $exportData = false, int $start = 0): string {
        $sql = "";
        if ($this->db->query("SHOW TABLES LIKE '%{$table}%'")) {
            
            // 表结构
            if ($start == 0) {
                $result = $this->db->query("SHOW CREATE TABLE `{$table}`");
                $sql .= "$this->eol-- -----------------------------$this->eol";
                $sql .= "-- 表结构 `{$table}`$this->eol";
                $sql .= "-- -----------------------------$this->eol";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;$this->eol";
                $sql .= trim($result[0]['Create Table']) . ";{$this->eol}{$this->eol}";
            }
            
            // 表数据
            if ($exportData) {
                $sql .= "-- -----------------------------$this->eol";
                $sql .= "-- 表数据 `{$table}`$this->eol";
                $sql .= "-- -----------------------------$this->eol";
                
                // 数据总数
                $result = $this->db->query("SELECT COUNT(*) AS count FROM `{$table}`");
                $count = $result['0']['count'];
                
                // 备份数据记录
                $result = $this->db->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
                foreach ($result as $row) {
                    $sql = "INSERT INTO `{$table}` VALUES (" . str_replace(["\r", "\n", "\r\n"], ['\r', '\n', '\r\n'], $this->getInsertValue($row)) . ");$this->eol";
                }
                
                // 还有更多数据
                if ($count > $start + 1000) {
                    $sql .= $this->getSql($table, $exportData, $start + 1000);
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
            
            $sql = "$this->eol";
            $sql .= "-- -----------------------------$this->eol";
            $sql .= "-- Table structure for `{$table}`$this->eol";
            $sql .= "-- -----------------------------$this->eol";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;$this->eol";
            $sql .= trim($result[0]['create table']) . ";{$this->eol}{$this->eol}";
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
                $sql = "-- -----------------------------$this->eol";
                $sql .= "-- Records of `{$table}`$this->eol";
                $sql .= "-- -----------------------------$this->eol";
                $this->write($sql);
            }
            
            // 备份数据记录
            $result = $this->db->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
            foreach ($result as $k => $row) {
                $sql = "INSERT INTO `{$table}` VALUES (" . str_replace(["\r", "\n", "\r\n"], ['\r', '\n', '\r\n'], $this->getInsertValue($row)) . ");$this->eol";
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
     * 获取数据库中所有表名
     * @return array
     */
    public function getTables(): array {
        try {
            $tables = [];
            $tableInfo = $this->db
                ->query('SELECT table_name FROM information_schema.tables WHERE table_schema=\'' . get_sub_value('database', $this->database, '') . '\'');
            foreach ($tableInfo as $k => $v) {
                $tables[] = is_array($v) ? array_values($v)[0] : $v;
            }
            return $tables;
        } catch (\Exception $exception) {
            return [];
        }
    }
    
    /**
     * 析构方法，用于关闭文件资源
     */
    public function __destruct() {
        $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
    }
}