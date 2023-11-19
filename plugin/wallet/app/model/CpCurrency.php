<?php

namespace plugin\wallet\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property string $name 全名
 * @property string $symbol 简称
 * @property integer $decimal_places 小数点位数
 * @property string $blockchain_network 区块链网络
 * @property string $contract_address 智能合约地址
 * @property string $status 状态
 * @property string $current_price 当前价格
 * @property string $network_fee 标准费用
 */
class CpCurrency extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cp_currencies';

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
