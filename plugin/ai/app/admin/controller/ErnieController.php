<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\Ernie;

/**
 * 百度千帆模型配置
 */
class ErnieController extends SettingBase
{

    /**
     * 服务名
     * @var string
     */
    protected $service = Ernie::class;

}
