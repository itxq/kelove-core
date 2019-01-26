<?php
/**
 *  ==================================================================
 *        文 件 名: App.php
 *        概    要: 应用管理
 *        作    者: IT小强
 *        创建时间: 2018-12-25 10:48:58
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\core;

/**
 * 应用管理
 * Class App
 * @package kelove\core
 */
class App extends \think\App
{
    /**
     * 运行根目录
     * @var string
     */
    protected $rootRuntimePath = '';
    
    /**
     * 路由根目录
     * @var string
     */
    protected $rootRoutePath = '';
    
    /**
     * 配置根目录
     * @var string
     */
    protected $rootConfigPath = '';
    
    /**
     * 自动多应用访问
     * @access public
     * @param  array $map 应用路由映射
     * @return $this
     */
    public function autoMulti(array $map = []) {
        $this->multi = true;
        $this->auto = true;
        if (!empty($this->request->path())) {
            $path = explode('/', trim($this->request->path(), '/'));
            $name = $path[0];
            if (isset($map[$name]) && $map[$name] instanceof \Closure) {
                $map[$name]($this);
            } elseif ($name) {
                $this->name = $map[$name] ?? $name;
            }
        }
        return $this;
    }
    
    /**
     * 设置应用基础目录
     * @param string $path 应用基础目录
     * @return $this
     */
    public function setBasePath(string $path) {
        $path = $this->createDir($path);
        if ($path) {
            $this->basePath = $path;
        }
        return $this;
    }
    
    /**
     * 设置运行根目录
     * @access public
     * @param string $path 运行根目录
     * @return $this
     */
    public function setRootRuntimePath(string $path) {
        $path = $this->createDir($path);
        if ($path) {
            $this->rootRuntimePath = $path;
        }
        return $this;
    }
    
    /**
     * 设置路由根目录
     * @access public
     * @param string $path 路由根目录
     * @return $this
     */
    public function setRootRoutePath(string $path) {
        $path = $this->createDir($path);
        if ($path) {
            $this->rootRoutePath = $path;
        }
        return $this;
    }
    
    /**
     * 设置配置根目录
     * @access public
     * @param string $path
     * @return $this
     */
    public function setRootConfigPath(string $path) {
        $path = $this->createDir($path);
        if ($path) {
            $this->rootConfigPath = $path;
        }
        return $this;
    }
    
    /**
     * 设置自定义依赖相关路径
     * @access protected
     * @param string $baseRoot
     * @return void
     */
    protected function setCustomDependPath(string $baseRoot): void {
        // 自定义运行目录
        if (!empty($this->rootRuntimePath)) {
            $this->runtimePath = str_replace($this->rootPath . 'runtime', realpath($this->rootRuntimePath), $this->runtimePath);
            $this->env->set(['runtime_path' => $this->runtimePath]);
        }
        // 自定义路由目录
        if (!empty($this->rootRoutePath)) {
            // $this->routePath = str_replace($this->rootPath . 'route', realpath($this->rootRoutePath), $this->routePath);
            $this->routePath = $this->rootRoutePath;
            $this->env->set(['route_path' => $this->routePath]);
        }
        // 自定义配置目录
        if (!empty($this->rootConfigPath)) {
            $this->configPath = str_replace($this->rootPath . 'config', realpath($this->rootConfigPath), $this->configPath);
            $this->env->set(['config_path' => $this->configPath]);
        }
        
        $this->env->set([
            'base_root'   => $baseRoot,
            'extend_path' => $baseRoot . 'extend' . DIRECTORY_SEPARATOR,
            'vendor_path' => $baseRoot . 'vendor' . DIRECTORY_SEPARATOR,
            'kelove_path' => realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR,
        ]);
    }
    
    /**
     * 设置依赖相关路径
     * @access protected
     * @return void
     */
    protected function setDependPath(): void {
        // 获取根目录
        $baseRoot = BASE_ROOT;
        if (!$this->appPath) {
            $this->appPath = $baseRoot . 'app' . ($this->multi ? DIRECTORY_SEPARATOR . $this->name : '') . DIRECTORY_SEPARATOR;
        }
        parent::setDependPath();
        $this->setCustomDependPath($baseRoot);
    }
    
    /**
     * 创建目录
     * @param string $path 路径
     * @return bool|string
     */
    protected function createDir(string $path) {
        if (is_dir($path)) {
            return realpath($path) . DIRECTORY_SEPARATOR;
        }
        $path = BASE_ROOT . $path;
        if (is_dir($path)) {
            return realpath($path) . DIRECTORY_SEPARATOR;
        }
        if (!mkdir($path, 0777, true)) {
            return false;
        }
        return realpath($path) . DIRECTORY_SEPARATOR;
    }
}
