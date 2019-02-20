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

use kelove\traits\SingleModelTrait;
use kelove\util\File;
use think\facade\Event;
use think\facade\Request;

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
     * @var array - 自动多应用，应用名映射
     */
    protected $autoMulti = [];
    
    /**
     * @var \kelove\core\App
     */
    protected $app;
    
    /**
     * 初始化加载
     * @param array $config - 配置信息
     */
    protected function initialize(array $config = []): void
    {
        ini_set('display_errors', 'Off');
        $this->scriptName = $this->getScriptName();
        $this->getBaseRoot();
        $this->kelovePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;
        $this->appList = $this->getAppList();
        $this->app = new App($this->kelovePath);
    }
    
    /**
     * 初始化控制台
     * @return App
     */
    public function console(): App
    {
        $this->app->setBasePath($this->kelovePath)
            ->setRootConfigPath('config' . DIRECTORY_SEPARATOR)
            ->setRootRuntimePath('runtime' . DIRECTORY_SEPARATOR)
            ->setRootConfigPath('config' . DIRECTORY_SEPARATOR)
            ->setRootRoutePath('route' . DIRECTORY_SEPARATOR)
            ->multi(false);
        return $this->app;
    }
    
    /**
     * 初始化应用
     * @param bool $autoMulti 是否自动多应用
     * @param string $name 应用名称
     * @return App
     */
    public function app(bool $autoMulti, string $name = ''): App
    {
        if ($autoMulti) {
            $this->app->autoMulti($this->autoMulti);
        }
        $autoName = $this->app->getName();
        if (in_array($autoName, array_keys($this->appList))) {
            $name = $autoName;
        }
        $appInfo = $this->appList[$name];
        $path = $appInfo['app_path'];
        $namespace = $appInfo['app_namespace'];
        $this->app
            ->name($name)
            ->setBasePath($this->kelovePath)
            ->setRootConfigPath('config' . DIRECTORY_SEPARATOR)
            ->setRootRuntimePath('runtime' . DIRECTORY_SEPARATOR)
            ->setRootConfigPath('config' . DIRECTORY_SEPARATOR)
            ->setRootRoutePath('route' . DIRECTORY_SEPARATOR)
            ->setNamespace($namespace)
            ->path($path);
        $appRoute = $path . 'route' . DIRECTORY_SEPARATOR;
        $defaultRoute = BASE_ROOT . 'route' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
        if (is_dir($appRoute) && !is_dir($defaultRoute)) {
            File::make()->copyDir($appRoute, $defaultRoute);
        }
        return $this->app;
    }
    
    /**
     * 获取全部应用列表
     * @return mixed
     */
    private function getAppList()
    {
        $list = [];
        // 获取注册的应用
        $appList = Event::trigger(self::APP_LIST_EVENT);
        // 获取app目录下的应用
        $appPath = BASE_ROOT . 'app' . DIRECTORY_SEPARATOR;
        if (is_dir($appPath)) {
            $dir = get_sub_value('dir', File::make()->getDirs($appPath), []);
            foreach ($dir as $v) {
                $infoFile = $appPath . $v . DIRECTORY_SEPARATOR . 'info.php';
                if (is_file($infoFile)) {
                    $appList[] = include $infoFile;
                }
            }
        }
        foreach ($appList as $v) {
            if (!$this->appInfoCheck($v)) {
                continue;
            }
            $v['app_path'] = realpath($v['app_path']) . DIRECTORY_SEPARATOR;
            $v['app_alias'] = get_sub_value('app_alias', $v, '');
            if (!empty($v['app_alias'])) {
                $this->autoMulti[$v['app_alias']] = $v['app_name'];
            } else {
                $v['app_alias'] = $v['app_name'];
            }
            $list[$v['app_name']] = $v;
        }
        return $list;
    }
    
    /**
     * 检查信息配置信息
     * @param array $info
     * @return bool
     */
    private function appInfoCheck(array $info): bool
    {
        if (
            !isset($info['app_name']) ||
            !isset($info['app_title']) ||
            !isset($info['app_path']) ||
            !is_dir($info['app_path']) ||
            !isset($info['app_namespace'])
        ) {
            return false;
        }
        return true;
    }
    
    /**
     * 获取根目录
     */
    private function getBaseRoot(): void
    {
        if (!defined('BASE_ROOT')) {
            $scriptName = realpath($this->scriptName);
            if (strrpos(basename($scriptName), '.php') === false) {
                $baseRoot = realpath(dirname($scriptName)) . DIRECTORY_SEPARATOR;
            } else {
                $baseFile = str_replace(['\\', '/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], Request::baseFile());
                $pattern = '#^(.*?)(' . addslashes($baseFile) . ')$#';
                $baseRoot = preg_replace($pattern, "$1", $scriptName);
                $baseRoot = realpath($baseRoot . '/../') . DIRECTORY_SEPARATOR;
            }
            define('BASE_ROOT', $baseRoot);
        }
    }
    
    /**
     * 获取入口文件路径
     * @return string
     */
    private function getScriptName(): string
    {
        return 'cli' == PHP_SAPI ? realpath($_SERVER['argv'][0]) : $_SERVER['SCRIPT_FILENAME'];
    }
}
