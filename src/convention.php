<?php

$config = include __DIR__ . '/convention_think.php';

// URL普通方式参数 用于自动生成
$config['app']['url_common_param'] = true;

// 模板引擎普通标签开始标记
$config['template']['tpl_begin'] = '{{';
// 模板引擎普通标签结束标记
$config['template']['tpl_end'] = '}}';
// 标签库标签开始标记
$config['template']['taglib_begin'] = '{{';
// 标签库标签结束标记
$config['template']['taglib_end'] = '}}';

return $config;
