<?php
/**
 * @author charles
 * @created 2023/10/27 19:14
 */

namespace plugin\wallet\app\service\block;

use plugin\wallet\app\dao\block\BlockWalletAddressDao;
use charles\basic\BaseService;
use support\Container;

class BlockWalletAddressService extends BaseService
{
    public function __construct(BlockWalletAddressDao $dao)
    {
        $this->dao = $dao;
    }

    public function addAddress($param)
    {
        /**
         * @var $networkService BlockNetworkService
         */
        $networkService = Container::get(BlockNetworkService::class);
        //获取可用的networkId
        $networkMap = $networkService->getColumn(['status' => 'active'], 'symbol', 'id');
        var_dump($networkMap);
        if (!in_array($param['network_id'], array_keys($networkMap))) {
            throw new \Exception('无可用网络');
        }
        $param['address'] = $param['address_base58'];
        return $this->dao->save($param);
    }
    public function getAddressesWithRecharge($networkId): array
    {
        return $this->dao->getAddressesWithRecharge($networkId);
    }

}