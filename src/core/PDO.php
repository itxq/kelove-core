<?php
/**
 *  ==================================================================
 *        文 件 名: PDO.php
 *        概    要: PDO连接
 *        作    者: IT小强
 *        创建时间: 2018-11-9 16:10
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\kelove\core;

use itxq\kelove\SingleModel;

/**
 * PDO连接
 * Class PDO
 * @package itxq\kelove\core
 */
class PDO extends SingleModel
{
    /**
     * @var null|\PDO - PDO实例
     */
    protected static $pdo = null;
    
    /**
     * 单利模式 - 返回本类对象
     * @param array $config - 配置信息
     * @param bool $force - 是否强制重新实例化
     * @return static
     */
    public static function ins($config = [], $force = false) {
        $className = get_called_class();
        if (!isset(self::$instances[$className]) || !self::$instances[$className] instanceof $className || $force === true) {
            $instance = new $className($config);
            self::$instances[$className] = $instance;
            self::$pdo = self::pdoIns($config);
        }
        return self::$instances[$className];
    }
    
    /**
     * 获取PDO实例
     * @return null|\PDO
     */
    public function getPdo() {
        return self::$pdo;
    }
    
    /**
     * PDO实例化
     * @param $config - 数据库配置信息
     * @return bool|\PDO
     */
    private static function pdoIns($config) {
        if ($config['type'] === 'mysql') {
            $dsn = "mysql:host=" . $config['hostname'] . ";port=" . $config['hostport'] . ";dbname=" . $config['database'] . ";charset=" . $config['charset'];
        } else {
            return false;
        }
        $options = [\PDO::ATTR_PERSISTENT => true, \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'];
        $pdo = new \PDO($dsn, $config['username'], $config['password'], $options);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        return $pdo;
    }
}