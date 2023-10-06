<?php

namespace plugin\ai\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $type 类型
 * @property string $value 值
 * @property string $log 日志
 * @property string $expired_at 有效期
 */
class AiBan extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_ban';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
