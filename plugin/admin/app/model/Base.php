<?php

namespace plugin\admin\app\model;

use charles\basic\BaseModel;
use DateTimeInterface;
use support\Model;


class Base extends BaseModel
{
    /**
     * @var string
     */
    protected $connection = 'plugin.admin.mysql';

    /**
     * 格式化日期
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
