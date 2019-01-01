<?php
/**
 *  ==================================================================
 *        文 件 名: Service.php
 *        概    要: 服务层基类
 *        作    者: IT小强
 *        创建时间: 2018-12-26 20:39:41
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\core;

use kelove\traits\SingleModelTrait;

/**
 * 服务层基类
 * Class Service
 * @package kelove\core
 */
abstract class Service
{
    use SingleModelTrait;
}