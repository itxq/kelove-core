<?php
/**
 *  ==================================================================
 *        文 件 名: kelove.php
 *        概    要: 入口文件
 *        作    者: IT小强
 *        创建时间: 2018-12-26 20:46:53
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

// KELOVE核心目录
define('KELOVE_PATH', realpath(__DIR__) . DIRECTORY_SEPARATOR);

// 根目录
if (!defined('ROOT_PATH')){
    define('ROOT_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
}

// 存储目录
define('STORAGE_PATH', ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR);


function getApp() {
    $app = new \kelove\core\App(KELOVE_PATH);
    $app->debug(false);
    $app->setBasePath(KELOVE_PATH);
    $app->setRootRuntimePath(STORAGE_PATH . 'runtime' . DIRECTORY_SEPARATOR);
    return $app;
}
