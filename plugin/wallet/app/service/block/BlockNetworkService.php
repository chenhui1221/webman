<?php
/**
 * @author charles
 * @created 2023/10/27 19:14
 */

namespace plugin\wallet\app\service\block;

use plugin\wallet\app\dao\block\BlockNetworkDao;
use charles\basic\BaseService;

class BlockNetworkService extends BaseService
{
    public function __construct(BlockNetworkDao $dao)
    {
        $this->dao = $dao;
    }

}