<?php
/**
 * @author charles
 * @created 2023/10/27 17:50
 */

namespace plugin\wallet\app\dao\block;

use charles\basic\BaseDao;
use plugin\wallet\app\model\BlockContract;

class BlockContractDao extends BaseDao
{

    protected function setModel(): string
    {
        return BlockContract::class;
    }

}