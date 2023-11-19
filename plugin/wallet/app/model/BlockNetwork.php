<?php

namespace plugin\wallet\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键ID(主键)
 * @property string $name 名称
 * @property string $symbol 符号
 * @property string $description 描述
 * @property string $rpc_url RPC URL
 * @property string $scan_url 浏览器
 * @property string $native_currency_symbol 原生
 * @property integer $chain_id 链ID
 * @property string $gas_limit Gas限制
 * @property string $gas_price Gas价格
 * @property integer $confirmation_blocks 确认区块
 * @property string $status 状态
 * @property mixed $created_at 创建时间
 * @property mixed $updated_at 更新时间
 */
class BlockNetwork extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'block_networks';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public function walletAddresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BlockWalletAddress::class, 'network_id');
    }

    public function contracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BlockContract::class, 'network_id')->select(['network_id','symbol','decimals','contract_address']);
    }



}
