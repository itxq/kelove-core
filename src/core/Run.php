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
        $this->kelovePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;
        $this->appList = $this->getAppList();
        $this->app = new App($this->kelovePath);
        $this->app->autoMulti();
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
            $v['app_path'] = realpath($v['app_path']) . DIRECTORY_SEPARATOR;
            $list[$v['app_name']] = $v;
        }
        return $list;
    }
    
    /**
     * 初始化应用
     * @param string $name 应用名称
     * @return App
     */
    public function app(string $name = ''): App {
        ini_set('display_errors', 'Off');
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
            ->setRootConfigPath($this->kelovePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR)
            ->setRootRuntimePath('runtime' . DIRECTORY_SEPARATOR)
            ->setNamespace($namespace)
            ->path($path);
        if (is_dir($path . 'route')) {
            $this->app->setRootRoutePath($path . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR);
        }
        return $this->app;
    }
    
    /**
     * 检查信息配置信息
     * @param array $info
     * @return bool
     */
    private function appInfoCheck(array $info): bool {
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
}
