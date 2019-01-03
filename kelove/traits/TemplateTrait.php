<?php
/**
 *  ==================================================================
 *        文 件 名: TemplateTrait.php
 *        概    要: 模板相关处理
 *        作    者: IT小强
 *        创建时间: 2019-01-01 19:20:43
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\traits;

use kelove\util\File;
use think\facade\Env;

/**
 * 模板相关处理
 * Trait TemplateTrait
 * @package kelove\traits
 */
trait TemplateTrait
{
    /**
     * 获取全部模板
     * @param string $name - 模板名称
     * @param string $path - 模板路径
     * @param string $suffix - 模板后缀
     * @return array
     */
    protected function getTemplate(string $name = '', string $path = '', string $suffix = ''): array {
        $name = empty($name) ? 'default' : $name;
        $suffix = empty($suffix) ? '.' . config('url_html_suffix') : $suffix;
        $path = empty($path) ? Env::get('base_root') . 'template' . DIRECTORY_SEPARATOR : realpath($path);
        $pre = $path . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
        $template = File::make()->getFiles($pre, true);
        $pattern = '/^(' . addslashes($pre) . ')(.*?)(\\' . addslashes($suffix) . ')$/';
        $cacheTag = 'system_template';
        $cacheName = $cacheTag . '_' . strtolower(md5($pattern));
        if (cache('?' . $cacheName)) {
            return cache($cacheName);
        }
        foreach ($template as $k => $v) {
            if (!preg_match($pattern, $k, $match)) {
                unset($template[$k]);
                continue;
            }
            $item = preg_replace($pattern, '\2', $k);
            $item = str_replace([DIRECTORY_SEPARATOR], ['/'], $item);
            $item = strtolower($item);
            unset($template[$k]);
            $template[$item] = $v;
        }
        cache($cacheName, $template, null, $cacheTag);
        return $template;
    }
}