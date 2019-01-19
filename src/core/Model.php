<?php
/**
 *  ==================================================================
 *        文 件 名: Model.php
 *        概    要: 数据模型基类
 *        作    者: IT小强
 *        创建时间: 2018-12-25 13:39:40
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\core;

use think\model\concern\SoftDelete;

/**
 * 数据模型基类
 * Class Model
 * @package kelove\core
 */
abstract class Model extends \think\Model
{
    use SoftDelete;
    
    /**
     * VARCHAR 最大值
     */
    const VARCHAR_MAX = 255;
    
    /**
     * INT 最大值
     */
    const INT_MAX = 11;
    
    /**
     * 软删除字段名称
     * @var string
     */
    protected $deleteTime = 'delete_time';
    
    /**
     * 软删除字段默认值
     * @var int
     */
    protected $defaultSoftDelete = 0;
    
    /**
     * 静态调用
     * @return static
     */
    public static function make() {
        return new static();
    }
    
    /**
     * 递归获取所有下级ID
     * @param int $id 父级分类ID，默认0，即获取所有分类ID
     * @param string $pidName 父级分类字段名称 默认为pid
     * @param int $loopTimes 获取分类层数（截止到n级分类）
     * @param int $countNum 记录分类层数,无需赋值
     * @param string $idList 记录id列表,无需赋值
     * @return string
     */
    public function getSonIds(int $id = 0, string $pidName = 'pid', int $loopTimes = 0, int $countNum = 0, string $idList = '') {
        /* 初始化 id 字符串列表 （首先把当前分类加入列表）*/
        $idList .= empty($idList) ? strval($id) : '';
        $where = [[$pidName, 'EQ', intval($id)], ['status', 'EQ', 1]];
        $select = $this->field('id')->where($where)->column('id');
        if (!is_array($select) || count($select) < 1) {
            return $idList;
        }
        /* 初始化 id 数组 */
        $countNum++;
        /* 执行生成字符串 */
        foreach ($select as $idValue) {
            $idList .= ',' . $idValue;
            if ($loopTimes === 0 || $countNum < $loopTimes) {
                $idList = $this->getSonIds($idValue, $pidName, $loopTimes, $countNum, $idList);
            }
        }
        return $idList;
    }
}