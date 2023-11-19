<?php

namespace plugin\wallet\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property string $network_id 网络id
 * @property string $symbol 货币符号
 * @property string $contract_address 合约地址
 * @property integer $decimals 精度
 * @property integer $status 状态
 * @property string $description 描述或备注
 * @property mixed $created_at 创建时间
 * @property mixed $updated_at 更新时间
 */
class BlockContract extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'block_contracts';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public function network(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BlockNetwork::class, 'network_id');
    }
    
}
