<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

class Category extends Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.category';

    /**获取配置
     *
     * @return array|mixed
     */
    public static function getSetting()
    {
        $categories = Option::where('name', static::OPTION_NAME)->value('value');
        if (!$categories || strpos($categories, "\n")) {
            $categories = "文案\n职业\n营销\n图片\n开发\n娱乐";
            static::saveSetting($categories);
            return $categories;
        }
        return json_decode($categories, true);
    }

}