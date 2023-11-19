<?php

namespace plugin\wallet\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property string $order_id 站内订单号
 * @property integer $from_user_id 发送方
 * @property integer $to_user_id 受益方
 * @property integer $network_id 网络ID
 * @property string $tx_id 交易ID（外部转账）
 * @property string $from_address 发送方地址
 * @property string $to_address 接收方地址
 * @property string $amount 转账金额
 * @property string $symbol 代币
 * @property string $fee 手续费
 * @property string $remark 备注
 * @property integer $status 状态
 * @property integer $is_internal 是否外部内部
 * @property string $block_time 交易时间
 * @property mixed $created_at 创建时间
 * @property mixed $updated_at 更新时间
 */
class Transaction extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
