<?php

namespace plugin\ai\app\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use plugin\admin\app\model\Base;
use plugin\admin\app\model\User;

/**
 * @property integer $id 主键(主键)
 * @property string $order_id 订单id
 * @property float $paid_amount 已支付总额
 * @property string $paid_at 支付时间
 * @property integer $state 状态
 * @property float $total_amount 须支付金额
 * @property string $updated_at 更新时间
 * @property string $created_at 创建时间
 * @property integer $user_id 用户id
 * @property string $data 业务数据
 * @property string payment_method 支付方式
 */
class AiOrder extends Base
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_orders';

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
