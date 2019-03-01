<?php
/**
 *  ==================================================================
 *        文 件 名: Base.php
 *        概    要: 验证器基类
 *        作    者: IT小强
 *        创建时间: 2019-01-26 09:33:34
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\validate;

use think\facade\Db;
use think\facade\Request;
use think\Validate;

/**
 * 验证器基类
 * Class Base
 * @package kelove\validate
 */
abstract class Base extends Validate
{
    /**
     * Base 构造函数.
     * @access public
     * @param  array $rules 验证规则
     * @param  array $message 验证提示信息
     * @param  array $field 验证字段描述信息
     */
    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        self::setTypeMsg('idCard', ':attribute格式错误！');
        self::setTypeMsg('uniqueCheck', ':attribute已存在');
        self::setTypeMsg('pkCheck', ':attribute不存在');
        parent::__construct($rules, $message, $field);
        $this->initialize();
    }
    
    /**
     * 身份证格式验证
     * @param string $value - 身份证号
     * @return bool
     */
    public function idCard(string $value): bool
    {
        $id = strtoupper($value);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        if (!preg_match($regx, $id)) {
            return false;
        }
        // 检查15位
        if (15 === strlen($id)) {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            if (!preg_match($regx, $id, $birth)) {
                return false;
            }
            //检查生日日期是否正确
            $birth = '19' . $birth[2] . '/' . $birth[3] . '/' . $birth[4];
            $birth = (int)strtotime($birth);
            return $birth > 0;
        }
        // 检查18位
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        if (!preg_match($regx, $id, $birth)) {
            return false;
        }
        $birth = $birth[2] . '/' . $birth[3] . '/' . $birth[4];
        $birth = (int)strtotime($birth);
        if ($birth <= 0) {   //检查生日日期是否正确
            return false;
        }
        //检验18位身份证的校验码是否正确。
        //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
        $int = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $ch = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sign = 0;
        for ($i = 0; $i < 17; $i++) {
            $b = (int)$id{$i};
            $w = $int[$i];
            $sign += $b * $w;
        }
        $n = $sign % 11;
        $num = $ch[$n];
        $id = $id[17];
        return (string)$num === (string)$id;
    }
    
    /**
     * 数据表字段唯一性验证
     * @param string $value - 验证数据
     * @param string $rule - 验证规则（数据表名称）
     * @param array $data - 全部数据（数组）
     * @param string $field - 字段名
     * @return bool
     */
    public function uniqueCheck(string $value, string $rule, array $data, string $field): bool
    {
        $model = Db::name($rule);
        $pk = $model->getPk();
        $info = Request::param();
        $id = get_sub_value($pk, $data, get_sub_value($pk, $info, 0));
        $where = [[$field, '=', $value]];
        if ($id !== 0) {
            $where[] = [$pk, '<>', $id];
        }
        $check = $model->where($where)->field($pk)->value($pk);
        return ((int)$check < 1);
    }
    
    /**
     * 验证主键是否存在
     * @param $value - 验证数据
     * @param $rule - 验证规则（数据表名称）
     * @return bool
     */
    public function pkCheck(int $value, string $rule): bool
    {
        $ruleInfo = explode(',', $rule);
        $noCheck = $ruleInfo[1] ?? false;
        if ($noCheck !== false && $value === (int)$noCheck) {
            return true;
        }
        $model = Db::name($ruleInfo[0]);
        $pk = $model->getPk();
        $where = [[$pk, '=', $value]];
        $check = $model->where($where)->field($pk)->value($pk);
        return ((int)$check >= 1);
    }
    
    /**
     * 初始化加载
     */
    protected function initialize(): void
    {
    
    }
}
