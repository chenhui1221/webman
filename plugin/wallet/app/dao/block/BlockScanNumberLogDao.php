<?php
/**
 * @author charles
 * @created 2023/11/10 18:02
 */

namespace plugin\wallet\app\dao\block;

use charles\basic\BaseDao;
use plugin\wallet\app\model\BlockScanNumberLog;

class BlockScanNumberLogDao extends BaseDao
{

    protected function setModel(): string
    {
        return BlockScanNumberLog::class;
    }
}