<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\SensitiveWord;

/**
 * 敏感词配置
 */
class SensitiveWordController extends SettingBase
{

    /**
     * 服务名
     * @var string
     */
    protected $service = SensitiveWord::class;

}
