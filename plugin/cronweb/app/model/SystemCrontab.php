<?php

namespace plugin\cronweb\app\model;

use plugin\admin\app\model\Base;

class SystemCrontab extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_crontab';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'type',
        'rule',
        'target',
        'status',
        'remark',
        'parameter',
        'singleton'
    ];

    public const UPDATED_AT = 'update_time';

    public const CREATED_AT = 'create_time';

    public $timestamps = false;
}
