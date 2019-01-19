<?php
/**
 *  ==================================================================
 *        文 件 名: event.php
 *        概    要: 事件定义
 *        作    者: IT小强
 *        创建时间: 2018-12-26 21:12:23
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

return [
    'bind'      => [],
    'listen'    => [
        'AppInit'      => ['think\listener\LoadLangPack', 'think\listener\RouteCheck'],
        'AppBegin'     => ['think\listener\CheckRequestCache'],
        'ActionBegin'  => [],
        'AppEnd'       => [],
        'LogLevel'     => [],
        'LogWrite'     => [],
        'ViewFilter'   => [],
        'ResponseSend' => [],
        'ResponseEnd'  => []
    ],
    'subscribe' => []
];