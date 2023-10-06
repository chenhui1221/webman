<?php

namespace plugin\nat\app\model;

use Illuminate\Database\Eloquent\SoftDeletes;
use support\Model;

/**
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $name 名称
 * @property string $domain 域名
 * @property string $local_ip 本地ip
 * @property integer $local_port 本地端口
 * @property integer $user_id 用户id
 */
class NatApp extends Model
{

    use SoftDeletes;

    /**
     * @var string
     */
    protected $connection = 'plugin.admin.mysql';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nat_apps';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
}
