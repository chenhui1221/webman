<?php

namespace plugin\nat\app\model;

use support\Model;

/**
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户id
 * @property string $token token
 */
class NatUser extends Model
{
    /**
     * @var string
     */
    protected $connection = 'plugin.admin.mysql';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nat_users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

}
