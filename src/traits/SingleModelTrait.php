<?php
/**
 *  ==================================================================
 *        文 件 名: SingleModelTrait.php
 *        概    要: 单例设计
 *        作    者: IT小强
 *        创建时间: 2018-12-25 13:48:57
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\traits;

/**
 * 单例设计
 * Trait SingleModelTrait
 * @package kelove\traits
 */
trait SingleModelTrait
{
    /**
     * @var array - 实例
     */
    protected static $instances = [];
    
    /**
     * @var array - 配置信息
     */
    protected $config = [];
    
    /**
     * @var mixed - 反馈信息
     */
    protected $message = '';
    
    /**
     * SingleModelTrait 构造函数. 禁止直接实例化该类
     * @param array $config - 配置信息
     */
    protected function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->initialize();
    }
    
    /**
     * 初始化加载
     */
    protected function initialize(): void
    {
    }
    
    /**
     * 单利模式 - 返回本类对象
     * @param array $config - 配置信息
     * @param bool $force - 是否强制重新实例化
     * @return static
     */
    public static function make(array $config = [], bool $force = false)
    {
        $className = get_called_class();
        if (!isset(self::$instances[$className]) || !self::$instances[$className] instanceof $className || $force === true) {
            $instance = new $className($config);
            self::$instances[$className] = $instance;
        }
        return self::$instances[$className];
    }
    
    /**
     * 获取反馈信息
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
    
    /**
     * 克隆防止继承
     * @return bool
     */
    final private function __clone()
    {
        return false;
    }
}
