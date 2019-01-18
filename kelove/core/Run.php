<?php
/**
 *  ==================================================================
 *        文 件 名: Run.php
 *        概    要: 初始化应用
 *        作    者: IT小强
 *        创建时间: 2019-01-01 21:03:09
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\core;

use Composer\Autoload\ClassLoader;
use kelove\traits\SingleModelTrait;
use think\Exception;

/**
 * 初始化应用
 * Class Run
 * @package kelove\core
 */
class Run
{
    use SingleModelTrait;
    
    /**
     * @var string 核心目录
     */
    protected $kelovePath = '';
    
    /**
     * 初始化加载
     * @param array $config - 配置信息
     */
    protected function initialize(array $config = []): void {
        $this->kelovePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;
    }
    
    /**
     * 初始化应用
     * @param \Composer\Autoload\ClassLoader $loader
     * @param bool $debug 是否开启调试
     * @param string $name 应用名称
     * @param string $namespace 应用命名空间
     * @param string $path 应用路径
     */
    public function appRun(ClassLoader $loader, bool $debug = false, string $name = '', string $namespace = '', string $path = ''): void {
        ini_set('display_errors', 'Off');
        if (empty($path)) {
            $path = $this->getNamespacePath($loader, $namespace);
        }
        try {
            $app = new App($this->kelovePath);
            $app->debug($debug)
                ->setBasePath($this->kelovePath)
                ->setRootRuntimePath('runtime' . DIRECTORY_SEPARATOR)
                ->name($name)
                ->setNamespace($namespace)
                ->path($path)
                ->run()->send();
        } catch (Exception $exception) {
            exit();
        }
    }
    
    /**
     * 获取命名空间路径
     * @param ClassLoader $loader
     * @param string $namespace 应用命名空间
     * @return string
     */
    protected function getNamespacePath(ClassLoader $loader, string $namespace): string {
        $psr4 = $loader->getPrefixesPsr4();
        if (!isset($psr4[$namespace . '\\'][0])) {
            return '';
        }
        $path = realpath($psr4[$namespace . '\\'][0]);
        if (!$path) {
            return '';
        }
        return $path . DIRECTORY_SEPARATOR;
    }
}