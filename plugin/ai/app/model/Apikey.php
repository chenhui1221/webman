<?php

namespace plugin\ai\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $apikey apikey
 * @property integer $state 禁用
 * @property string $last_error 错误信息
 * @property integer $error_count 错误次数
 * @property string $last_message_at 消息时间
 * @property integer $message_count 消息数
 * @property integer $gpt4 支持gpt4
 * @property integer $suspended 停用
 */
class Apikey extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_apikeys';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
