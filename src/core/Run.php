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
use think\facade\Event;

/**
 * 初始化应用
 * Class Run
 * @package kelove\core
 */
class Run
{
    use SingleModelTrait;
    
    /**
     * 应用列表事件标识
     */
    const APP_LIST_EVENT = 'kelove_app_list';
    
    /**
     * @var string 应用入口文件
     */
    protected $scriptName;
    
    /**
     * @var string 核心目录
     */
    protected $kelovePath;
    
    /**
     * @var array 应用列表
     */
    protected $appList = [];
    
    /**
     * @var \kelove\core\App
     */
    protected $app;
    
    /**
     * 初始化加载
     * @param array $config - 配置信息
     */
    protected function initialize(array $config = []): void {
        $this->scriptName = $this->getScriptName();
        $this->kelovePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;
        $this->appList = $this->getAppList();
        $this->app = new App($this->kelovePath);
    }
    
    /**
     * 获取全部应用列表
     * @return mixed
     */
    protected function getAppList() {
        $list = [];
        $appList = Event::trigger(self::APP_LIST_EVENT);
        foreach ($appList as $v) {
            if (!$this->appInfoCheck($v)) {
                continue;
            }
            $list[$v['app_name']] = $v;
        }
        return $list;
    }
    
    /**
     * 初始化应用
     * @param string $name 应用名称
     * @param bool $debug 是否开启调试
     * @param string $namespace 应用命名空间
     * @param string $path 应用路径
     */
    public function appRun(string $name = '', bool $debug = false): void {
        ini_set('display_errors', 'Off');
        if (empty($name)) {
            $name = pathinfo($this->scriptName, PATHINFO_FILENAME);
        }
        try {
            $appInfo = $this->appList[$name];
            $path = $appInfo['app_path'];
            $namespace = $appInfo['app_namespace'];
            $this->app->debug($debug)
                ->setBasePath($this->kelovePath)
                ->setRootRuntimePath('runtime' . DIRECTORY_SEPARATOR)
                ->name($name)
                ->setNamespace($namespace)
                ->path($path)
                ->run()->send();
        } catch
        (Exception $exception) {
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
    
    /**
     * 检查信息配置信息
     * @param array $info
     * @return bool
     */
    protected function appInfoCheck(array $info): bool {
        if (
            !isset($info['app_name']) ||
            !isset($info['app_title']) ||
            !isset($info['app_path']) ||
            !isset($info['app_namespace'])
        ) {
            return false;
        }
        return true;
    }
    
    /**
     * 获取入口文件
     * @return string
     */
    protected function getScriptName(): string {
        return 'cli' == PHP_SAPI ? realpath($_SERVER['argv'][0]) : $_SERVER['SCRIPT_FILENAME'];
    }
}