<?php
/**
 *  ==================================================================
 *        文 件 名: Database.php
 *        概    要: 数据库操作类
 *        作    者: IT小强
 *        创建时间: 2018-11-09 15:56
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\kelove\core;

use itxq\kelove\SingleModel;

/**
 * 数据库操作类
 * Class Database
 * @package itxq\kelove\core
 */
class Database extends SingleModel
{
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
        'database' => [],
    ];
    
    /**
     * @var array - 数据库配置
     */
    protected $database = [];
    
    /**
     * @var null|\PDO - pdo实例
     */
    protected $pdo = null;
    
    /**
     * 数据库备份构造方法
     * @param array $config - 备份配置信息
     */
    protected function __construct($config = []) {
        parent::__construct($config);
        $this->file = get_sub_value('file', $config, []);
        $this->database = get_sub_value('database', $config, []);
        $this->pdo = PDO::ins($this->database)->getPdo();
    }
    
    /**
     * 写入初始数据
     * @return mixed
     */
    public function create() {
        $sql = "-- -----------------------------\n";
        $sql .= "-- MySQL Data Transfer\n";
        $sql .= "--\n";
        $sql .= "-- Host     : " . get_sub_value('hostname', $this->database, '127.0.0.1') . "\n";
        $sql .= "-- Port     : " . get_sub_value('hostport', $this->database, '3306') . "\n";
        $sql .= "-- Database : " . get_sub_value('database', $this->database, '') . "\n";
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
    public function backupAll($tables = [], $start = 0) {
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
    public function importAll($infoList) {
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
     * @param array|string $tables 表名
     * @param string $path 导出路径
     * @param string $prefix 表前缀
     * @param integer $export_data 是否导出数据
     * @return bool
     */
    public function export($tables = [], $path = '', $prefix = '', $export_data = 1) {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
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
     * @param array|string $tables 表名
     * @param string $path 导出路径
     * @param string $prefix 表前缀
     * @return bool
     */
    public function exportUninstall($tables = [], $path = '', $prefix = '') {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
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
     * @param integer $export_data 是否导出数据
     * @param integer $start 起始行数
     * @return string
     */
    public function getSql($table = '', $export_data = 0, $start = 0) {
        $sql = "";
        if ($this->pdo->query("SHOW TABLES LIKE '%{$table}%'")) {
            // 表结构
            if ($start == 0) {
                $result = $this->pdo->query("SHOW CREATE TABLE `{$table}`");
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
                $result = $this->pdo->query("SELECT COUNT(*) AS count FROM `{$table}`");
                $count = $result['0']['count'];
                
                // 备份数据记录
                $result = $this->pdo->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
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
     * @param integer $start 起始行数
     * @return array|bool|int  false - 备份失败
     */
    private function backup($table = '', $start = 0) {
        // 备份表结构
        if (0 == $start) {
            $result = $this->pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            $sql = "\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "-- Table structure for `{$table}`\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= trim($result[1]) . ";\n\n";
            if (false === $this->write($sql)) {
                return false;
            }
        }
        
        // 数据总数
        $result = $this->pdo->query("SELECT COUNT(*) AS count FROM `{$table}`")->fetch(\PDO::FETCH_NUM);
        $count = $result['0'];
        
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
            $result = $this->pdo->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000")->fetchAll(\PDO::FETCH_NUM);
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
    private function getInsertValue($row) {
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
     * @param integer $start 起始位置
     * @return array|bool|int
     */
    private function import($start = 0) {
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
                if (false !== $this->pdo->exec($sql)) {
                    $start += strlen($sql);
                } else {
                    return false;
                }
                $sql = '';
            } elseif ($this->config['compress'] ? gzeof($gz) : feof($gz)) {
                return 0;
            }
        }
        
        return array($start, $size);
    }
    
    /**
     * 打开一个卷，用于写入数据
     * @param integer $size 写入数据的大小
     */
    private function open($size = 0) {
        if ($this->fp) {
            $this->size += $size;
            if ($this->size > $this->config['part']) {
                $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
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
     * @return int
     */
    private function write($sql = '') {
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
    public function getTables() {
        try {
            return $this->pdo->query('show tables')->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $exception) {
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