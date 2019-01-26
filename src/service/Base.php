<?php
/**
 *  ==================================================================
 *        文 件 名: Base.php
 *        概    要: 服务层基类
 *        作    者: IT小强
 *        创建时间: 2019-01-26 09:32:04
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\service;

use kelove\traits\SingleModelTrait;

/**
 * 服务层基类
 * Class Base
 * @package kelove\service
 */
abstract class Base
{
    use SingleModelTrait;
}
