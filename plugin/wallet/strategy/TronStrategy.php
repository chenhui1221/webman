<?php
/**
 * @author charles
 * @created 2023/10/23 17:58
 */

namespace plugin\wallet\strategy;

use plugin\wallet\contracts\BlockchainInterface;
use plugin\wallet\contracts\BlockchainStrategyInterface;
use plugin\wallet\service\block\TronBlockchain;

class TronStrategy implements BlockchainStrategyInterface
{
    public static function create(array $config): BlockchainInterface
    {
        //这里可以各种配置话
        return new TronBlockchain();
    }
}