<?php
/**
 *  ==================================================================
 *        文 件 名: Tree.php
 *        概    要: 通用树型类
 *        作    者: IT小强
 *        创建时间: 2018-11-09 15:13
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\kelove\core;

use itxq\kelove\SingleModel;

/**
 * 通用树型类
 * Class Tree
 * @package itxq\kelove\core
 */
class Tree extends SingleModel
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'id'     => 'id',    // id名称
        'pid'    => 'pid',   // pid名称
        'title'  => 'title', // 标题名称
        'child'  => 'child', // 子元素键名
        'parent' => 'parent', // 父元素键名
        'html'   => '┝ ',   // 层级标记
        'repeat' => ' . ',   // 重复标志
        'step'   => 3,       // 层级步进数量
        'end'    => false,       // 前缀/后缀
    ];
    
    /**
     * 将数据集格式化成层次结构
     * @param array /object $lists 要格式化的数据集，可以是数组，也可以是对象
     * @param int $pid 父级id
     * @param int $maxLevel 最多返回多少层，0为不限制
     * @param int $curLevel 当前层数
     * @return array
     */
    public function toLayer($lists = [], $pid = 0, $maxLevel = 0, $curLevel = 0) {
        $trees = [];
        $lists = array_values($lists);
        foreach ($lists as $key => $value) {
            if ($value[$this->config['pid']] == $pid) {
                if ($maxLevel > 0 && $curLevel == $maxLevel) {
                    return $trees;
                }
                unset($lists[$key]);
                $child = self::toLayer($lists, $value[$this->config['id']], $maxLevel, $curLevel + 1);
                if (!empty($child)) {
                    $value[$this->config['child']] = $child;
                }
                $trees[] = $value;
            }
        }
        return $trees;
    }
    
    /**
     * 将数据集格式化成列表结构
     * @param  array|object $lists 要格式化的数据集，可以是数组，也可以是对象
     * @param  integer $pid 父级id
     * @param  integer $level 级别
     * @param  array $parent 父级信息(自动生成，无需赋值)
     * @return array 列表结构(一维数组)
     */
    public function toList($lists = [], $pid = 0, $level = 0, $parent = []) {
        if (is_array($lists)) {
            $trees = [];
            foreach ($lists as $key => $value) {
                if ($value[$this->config['pid']] == $pid) {
                    $title_prefix = str_repeat($this->config['repeat'], $level * $this->config['step']);
                    if ($this->config['end']) {
                        $title_prefix = $this->config['html'] . $title_prefix;
                    } else {
                        $title_prefix = $title_prefix . $this->config['html'];
                    }
                    $value[$this->config['parent']] = $parent;
                    $value['level'] = $level + 1;
                    $value['title_prefix'] = $title_prefix;
                    $value['title_display'] = $title_prefix . $value[$this->config['title']];
                    $trees[] = $value;
                    unset($lists[$key]);
                    $trees = array_merge($trees, self::toList($lists, $value[$this->config['id']], $level + 1, $value));
                }
            }
            return $trees;
        } else {
            foreach ($lists as $key => $value) {
                if ($value[$this->config['pid']] == $pid && is_object($value)) {
                    $title_prefix = str_repeat($this->config['repeat'], $level * $this->config['step']) . $this->config['html'];
                    $value['level'] = $level + 1;
                    $value['title_prefix'] = $level == 0 ? '' : $title_prefix;
                    $value['title_display'] = $level == 0 ? $value[$this->config['title']] : $title_prefix . $value[$this->config['title']];
                    $lists->offsetUnset($key);
                    $lists[] = $value;
                    self::toList($lists, $value[$this->config['id']], $level + 1);
                }
            }
            return $lists;
        }
    }
    
    /**
     * 根据子节点返回所有父节点
     * @param  array $lists 数据集
     * @param  string $id 子节点id
     * @return array
     */
    public function getParents($lists = [], $id = '') {
        $trees = [];
        foreach ($lists as $value) {
            if ($value[$this->config['id']] == $id) {
                $trees[] = $value;
                $trees = array_merge(self::getParents($lists, $value[$this->config['pid']]), $trees);
            }
        }
        return $trees;
    }
    
    /**
     * 获取所有子节点id
     * @param  array $lists 数据集
     * @param  string $pid 父级id
     * @return array
     */
    public function getChildId($lists = [], $pid = '') {
        $result = [];
        foreach ($lists as $value) {
            if ($value[$this->config['pid']] == $pid) {
                $result[] = $value[$this->config['id']];
                $result = array_merge($result, self::getChildId($lists, $value[$this->config['frameworkw']]));
            }
        }
        return $result;
    }
    
    /**
     * 获取所有子节点
     * @param  array $lists 数据集
     * @param  string $pid 父级id
     * @return array
     */
    public function getChild($lists = [], $pid = '') {
        $result = [];
        foreach ($lists as $value) {
            if ($value[$this->config['pid']] == $pid) {
                $result[] = $value;
                $result = array_merge($result, self::getChild($lists, $value[$this->config['id']]));
            }
        }
        return $result;
    }
}