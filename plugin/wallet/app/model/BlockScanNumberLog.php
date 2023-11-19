<?php

namespace plugin\wallet\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 日志ID(主键)
 * @property string $tag 区块标签
 * @property mixed $cron_at 定时任务时间
 * @property integer $online_block_height 线上高度
 * @property integer $local_block_height 本地高度
 * @property string $transaction 交易记录详情
 * @property mixed $created_at 创建时间
 * @property mixed $updated_at 修改时间
 */
class BlockScanNumberLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'block_scan_number_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
