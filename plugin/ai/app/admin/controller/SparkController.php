<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\Spark;

/**
 * 百度千帆模型配置
 */
class SparkController extends SettingBase
{

    /**
     * 服务名
     * @var string
     */
    protected $service = Spark::class;

}
