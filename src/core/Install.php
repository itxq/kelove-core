<?php
/**
 *  ==================================================================
 *        文 件 名: Install.php
 *        概    要: 安装处理类
 *        作    者: IT小强
 *        创建时间: 2019-02-24 12:13:07
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\core;

use kelove\traits\SingleModelTrait;
use kelove\util\File;

/**
 * Class Install
 * @title 安装处理类
 * @package kelove\core
 * @author IT小强
 * @createTime 2019-02-24 12:46:35
 */
class Install
{
    use SingleModelTrait;
    
    /**
     * @title 路由安装
     * @author IT小强
     * @createTime 2019-02-24 12:22:45
     * @param string $appPath - 应用目录
     * @param string $appName - 应用名称
     * @param string $routePath - 路由目录
     * @param bool $isCover - 是否覆盖安装
     * @return bool
     */
    public function installRoute(string $appPath, string $appName, string $routePath, bool $isCover = false): bool
    {
        $appRoute = realpath($appPath) . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR;
        if (!is_dir($appRoute)) {
            // 该应用下无路由
            return true;
        }
        $defaultRoute = realpath($routePath) . DIRECTORY_SEPARATOR . $appName . DIRECTORY_SEPARATOR;
        if ($isCover === false && is_dir($defaultRoute)) {
            // 路由已安装
            return true;
        }
        return File::make()->copyDir($appRoute, $defaultRoute);
    }
    
    /**
     * @title 资源安装
     * @author IT小强
     * @createTime 2019-02-24 12:28:09
     * @param string $appPath - 应用目录
     * @param string $appName - 应用名称
     * @param string $assetsPath - 资源目录
     * @param bool $isCover - 是否覆盖安装
     * @return bool
     */
    public function installAssets(string $appPath, string $appName, string $assetsPath, bool $isCover = false): bool
    {
        if ('cli' === PHP_SAPI) {
            // cli模式下不安装资源
            return true;
        }
        $appRoute = realpath($appPath) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        if (!is_dir($appRoute)) {
            // 该应用下无资源
            return true;
        }
        $defaultRoute = realpath($assetsPath) . DIRECTORY_SEPARATOR . $appName . DIRECTORY_SEPARATOR;
        if ($isCover === false && is_dir($defaultRoute)) {
            // 资源已安装
            return true;
        }
        return File::make()->copyDir($appRoute, $defaultRoute);
    }
}
