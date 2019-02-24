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
use think\exception\HttpException;
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
        $this->config = $config;
        error_reporting(E_ALL);
        ini_set('display_errors', 'Off');
        $this->getBaseRoot();
        $this->kelovePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;
        $this->appList = $this->getAppList();
        $this->app = new App(ROOT_PATH);
        $this->app
            ->setRootConfigPath($this->kelovePath . 'config' . DIRECTORY_SEPARATOR)
            ->setRootRuntimePath(ROOT_PATH . 'runtime' . DIRECTORY_SEPARATOR)
            ->setRootRoutePath($this->kelovePath . 'route' . DIRECTORY_SEPARATOR);
    }
    
    /**
     * 初始化控制台
     * @return App
     */
    public function console(): App
    {
        $this->app->multi(false);
        return $this->app;
    }
    
    /**
     * 初始化应用
     * @param bool $autoMulti 是否自动多应用
     * @param bool $debug 是否开启调试模式
     * @param string $name 应用名称
     * @return $this
     */
    public function app(bool $autoMulti, $debug = false, string $name = ''): Run
    {
        if ($autoMulti) {
            $this->app->autoMulti($this->autoMulti);
        }
        // 获取应用名称
        $autoName = $this->app->getName();
        $name = empty($autoName) ? $name : $autoName;
        // 检查应用是否已注册
        if (!in_array($name, array_keys($this->appList))) {
            try {
                $this->app->debug($debug)->name($name)->run();
            } catch (\Exception $e) {
                throw new HttpException(404, 'application not exists:' . $name);
            }
        }
        // 初始化应用信息
        $appInfo = $this->appList[$name];
        $path = $appInfo['app_path'];
        $namespace = $appInfo['app_namespace'];
        $this->app->debug($debug)->path($path)->name($name)->setNamespace($namespace);
        // 安装路由
        Install::make()->installRoute($path, $name, $this->kelovePath . 'route' . DIRECTORY_SEPARATOR, false);
        return $this;
    }
    
    /**
     * 执行应用程序并发送数据到客户端
     */
    public function run()
    {
        $this->app->run()->send();
    }
    
    /**
     * 获取全部应用列表
     * @return mixed
     */
    protected function getAppList()
    {
        $list = [];
        // 获取app目录下的应用
        $appPath = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR;
        if (is_dir($appPath)) {
            $dir = get_sub_value('dir', File::make()->getDirs($appPath), []);
            foreach ($dir as $v) {
                $infoFile = $appPath . $v . DIRECTORY_SEPARATOR . 'info.php';
                if (is_file($infoFile)) {
                    include_once $infoFile;
                }
            }
        }
        // 获取注册的应用
        $appList = Event::trigger(self::APP_LIST_EVENT);
        $install = Install::make();
        foreach ($appList as $v) {
            if (!$this->appInfoCheck($v)) {
                continue;
            }
            $v['app_alias'] = get_sub_value('app_alias', $v, '');
            if (!empty($v['app_alias'])) {
                $this->autoMulti[$v['app_alias']] = $v['app_name'];
            } else {
                $v['app_alias'] = $v['app_name'];
            }
            $list[$v['app_name']] = $v;
            $install->installAssets($v['app_path'], $v['app_name'], PUBLIC_PATH, false);
        }
        return $list;
    }
    
    /**
     * 检查信息配置信息
     * @param array $info
     * @return bool
     */
    protected function appInfoCheck(array $info): bool
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
    protected function getBaseRoot(): void
    {
        $this->scriptName = $this->getScriptName();
        if (!defined('PUBLIC_PATH')) {
            define('PUBLIC_PATH', realpath(dirname($this->scriptName)) . DIRECTORY_SEPARATOR);
        }
        if (!defined('ROOT_PATH')) {
            $scriptName = realpath($this->scriptName);
            if (strrpos(basename($scriptName), '.php') === false) {
                $baseRoot = realpath(dirname($scriptName)) . DIRECTORY_SEPARATOR;
            } else {
                $baseFile = str_replace(['\\', '/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], Request::baseFile());
                $pattern = '#^(.*?)(' . addslashes($baseFile) . ')$#';
                $baseRoot = preg_replace($pattern, "$1", $scriptName);
                $baseRoot = realpath($baseRoot . '/../') . DIRECTORY_SEPARATOR;
            }
            define('ROOT_PATH', $baseRoot);
        }
    }
    
    /**
     * 获取入口文件路径
     * @return string
     */
    protected function getScriptName(): string
    {
        return 'cli' == PHP_SAPI ? realpath($_SERVER['argv'][0]) : $_SERVER['SCRIPT_FILENAME'];
    }
}
