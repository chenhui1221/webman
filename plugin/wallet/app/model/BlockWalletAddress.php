<?php

namespace plugin\wallet\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 地址ID，主键(主键)
 * @property integer $user_id 用户ID
 * @property string $network_id 区块链网络
 * @property string $address 钱包地址
 * @property string $private_key 私钥
 * @property integer $type 地址类型
 * @property integer $is_default 默认地址
 * @property integer $last_checked_block 最后区块高度
 * @property string $collection_threshold 资金归集阈值
 * @property mixed $created_at 创建时间
 * @property mixed $updated_at 更新时间
 */
class BlockWalletAddress extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'block_wallet_address';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    const TYPE_RECHARGE = 1;//充值
    const TYPE_OTHER = 2; //其他
    protected $fillable=[
        'user_id',
        'network_id',
        'address',
        'private_key',
    ];
    public function network(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BlockNetwork::class, 'network_id')->select(['id','name','native_currency_symbol']);
    }
    // 定义访问器

    
}
