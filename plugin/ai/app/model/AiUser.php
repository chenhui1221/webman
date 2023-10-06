<?php

namespace plugin\ai\app\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use plugin\admin\app\model\Base;
use plugin\admin\app\model\User;

/**
 * @property integer $id 主键(主键)
 * @property integer $user_id 用户id
 * @property string $expired_at 过期时间
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $deleted_at 删除时间
 * @property integer $message_count 对话数
 * @property integer $available_gpt3 对话数
 * @property integer $available_gpt4 对话数
 * @property integer $available_dalle 对话数
 * @property integer $available_midjourney 对话数
 */
class AiUser extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @return BelongsTo
     */
    public function base(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
}
