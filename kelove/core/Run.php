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

// KELOVE核心目录
define('KELOVE_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);

// 根目录
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR);
}

// 存储目录
define('STORAGE_PATH', ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR);

/**
 * 初始化应用
 * Class Run
 * @package kelove\core
 */
class Run
{
    use SingleModelTrait;
    
    /**
     * 初始化应用
     * @param bool $debug 是否开启调试
     * @param string $name 应用名称
     * @param string $namespace 应用命名空间
     * @param string $path 应用路径
     */
    public function appRun(bool $debug = false, string $name = '', string $namespace = '', string $path = ''): void {
        $app = new App(KELOVE_PATH);
        $app->debug($debug)
            ->setBasePath(KELOVE_PATH)
            ->setRootRuntimePath(STORAGE_PATH . 'runtime' . DIRECTORY_SEPARATOR)
            ->name($name)
            ->setNamespace($namespace)
            ->path($path)
            ->run()->send();
    }
}