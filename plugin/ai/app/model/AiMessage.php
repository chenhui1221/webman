<?php

namespace plugin\ai\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户id
 * @property string $session_id session id
 * @property integer $role_id 角色id
 * @property string $model 模型
 * @property integer $chat_id 对话id
 * @property integer $message_id 消息id
 * @property string $role 角色
 * @property mixed $content 内容
 * @property string $ip ip
 */
class AiMessage extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_messages';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
