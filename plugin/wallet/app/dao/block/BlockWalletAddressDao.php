<?php
/**
 * @author charles
 * @created 2023/10/27 17:50
 */

namespace plugin\wallet\app\dao\block;

use charles\basic\BaseDao;
use Illuminate\Support\Collection;
use plugin\wallet\app\model\BlockWalletAddress;

class BlockWalletAddressDao extends BaseDao
{

    protected function setModel(): string
    {
        return BlockWalletAddress::class;
    }


    /**
     * 应该查询最近更新时间7天内的数据，用户每次打开充值更新地址时间戳
     * @param $networkId
     * @return array
     */
    public function getAddressesWithRecharge($networkId): array
    {
        return $this->getColumn(['type' => BlockWalletAddress::TYPE_RECHARGE, 'network_id' => $networkId], 'address');

    }
}