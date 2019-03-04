<?php
/**
 *  ==================================================================
 *        文 件 名: Base.php
 *        概    要: 数据模型基类
 *        作    者: IT小强
 *        创建时间: 2019-01-26 09:29:57
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 数据模型基类
 * Class Base
 * @package kelove\model
 */
abstract class Base extends Model
{
    use SoftDelete;
    
    /**
     * VARCHAR 最大值
     */
    public const VARCHAR_MAX = 191;
    
    /**
     * INT 最大值
     */
    public const INT_MAX = 11;
    
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
     * 递归获取所有下级ID
     * @param int $id 父级分类ID，默认0，即获取所有分类ID
     * @param string $pid 父级分类字段名称 默认为pid
     * @param int $loop 获取分类层数（截止到n级分类）
     * @param int $count 记录分类层数,无需赋值
     * @param string $result 记录id列表,无需赋值
     * @return string
     */
    public function getSonIds(
        int $id = 0,
        string $pid = 'pid',
        int $loop = 0,
        int $count = 0,
        string $result = ''
    ): string {
        /* 初始化 id 字符串列表 （首先把当前分类加入列表）*/
        $result .= empty($result) ? (string)$id : '';
        $where = [[$pid, 'EQ', $id], ['status', 'EQ', 1]];
        $select = self::field('id')->where($where)->column('id');
        if (!is_array($select) || count($select) < 1) {
            return $result;
        }
        /* 初始化 id 数组 */
        $count++;
        /* 执行生成字符串 */
        foreach ($select as $idValue) {
            $result .= ',' . $idValue;
            if ($loop === 0 || $count < $loop) {
                $result = $this->getSonIds($idValue, $pid, $loop, $count, $result);
            }
        }
        return $result;
    }
}
