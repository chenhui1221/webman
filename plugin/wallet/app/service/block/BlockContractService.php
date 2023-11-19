<?php
/**
 * @author charles
 * @created 2023/10/27 19:14
 */

namespace plugin\wallet\app\service\block;

use plugin\wallet\app\dao\block\BlockContractDao;
use charles\basic\BaseService;

class BlockContractService extends BaseService
{
    public function __construct(BlockContractDao $dao)
    {
        $this->dao = $dao;
    }

    public function getContractByAddress($address)
    {
        return $this->dao->get($address);
    }

}