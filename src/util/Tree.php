<?php
/**
 *  ==================================================================
 *        文 件 名: Tree.php
 *        概    要: 通用树型类
 *        作    者: IT小强
 *        创建时间: 2018-12-26 21:24
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\util;

use kelove\traits\SingleModelTrait;

/**
 * 通用树型类
 * Class Tree
 * @package kelove\util
 */
class Tree
{
    use SingleModelTrait;
    
    /**
     * 默认配置参数
     * @var array
     */
    protected $defaultConfig = [
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
     * 初始化
     * @param array $config
     */
    protected function initialize(array $config = []): void
    {
        $this->config = array_merge($this->defaultConfig, $config);
    }
    
    /**
     * 将数据集格式化成层次结构
     * @param array $lists 要格式化的数组
     * @param int $pid 父级id
     * @param int $maxLevel 最多返回多少层，0为不限制
     * @param int $curLevel 当前层数
     * @return array
     */
    public function toLayer(array $lists = [], int $pid = 0, int $maxLevel = 0, int $curLevel = 0): array
    {
        $trees = [];
        $lists = array_values($lists);
        foreach ($lists as $key => $value) {
            if ($value[$this->config['pid']] == $pid) {
                if ($maxLevel > 0 && $curLevel == $maxLevel) {
                    return $trees;
                }
                unset($lists[$key]);
                $child = $this->toLayer($lists, $value[$this->config['id']], $maxLevel, $curLevel + 1);
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
     * @param  array $lists 要格式化的数组
     * @param  int $pid 父级id
     * @param  int $level 级别
     * @param  array $parent 父级信息(自动生成，无需赋值)
     * @return array 列表结构(一维数组)
     */
    public function toList(array $lists = [], int $pid = 0, int $level = 0, array $parent = []): array
    {
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
                $trees = array_merge($trees, $this->toList($lists, $value[$this->config['id']], $level + 1, $value));
            }
        }
        return $trees;
    }
    
    /**
     * 根据子节点返回所有父节点
     * @param  array $lists 数据集
     * @param  int $id 子节点id
     * @return array
     */
    public function getParents(array $lists = [], int $id = 0): array
    {
        $trees = [];
        foreach ($lists as $value) {
            if ($value[$this->config['id']] == $id) {
                $trees[] = $value;
                $trees = array_merge($this->getParents($lists, $value[$this->config['pid']]), $trees);
            }
        }
        return $trees;
    }
    
    /**
     * 获取所有子节点id
     * @param  array $lists 数据集
     * @param  int $pid 父级id
     * @return array
     */
    public function getChildrenIds(array $lists = [], int $pid = 0): array
    {
        $result = [];
        foreach ($lists as $value) {
            if ($value[$this->config['pid']] == $pid) {
                $result[] = $value[$this->config['id']];
                $result = array_merge($result, $this->getChildrenIds($lists, $value[$this->config['id']]));
            }
        }
        return $result;
    }
    
    /**
     * 获取所有子节点
     * @param  array $lists 数据集
     * @param  int $pid 父级id
     * @return array
     */
    public function getChildren(array $lists = [], int $pid = 0): array
    {
        $result = [];
        foreach ($lists as $value) {
            if ($value[$this->config['pid']] == $pid) {
                $result[] = $value;
                $result = array_merge($result, $this->getChildren($lists, $value[$this->config['id']]));
            }
        }
        return $result;
    }
}
