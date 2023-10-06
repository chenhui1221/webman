<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\Category;

/**
 * 角色分类配置
 */
class CategoryController extends SettingBase
{

    /**
     * 服务名
     * @var string
     */
    protected $service = Category::class;

}
