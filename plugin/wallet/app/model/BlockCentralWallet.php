<?php

namespace plugin\wallet\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id (主键)
 * @property string $address 地址
 * @property string $private_key 私钥
 * @property integer $status 状态
 * @property integer $bandwidth 带宽
 * @property integer $energy 能量
 * @property string $network_id 网络id
 * @property mixed $last_updated 更新时间
 */
class BlockCentralWallet extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'block_central_wallets';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    
    
}
