<?php
/**
 * @author charles
 * @created 2023/10/23 17:57
 */

namespace plugin\wallet\contracts;

interface BlockchainStrategyInterface
{
    public static function create(array $config): BlockchainInterface;

}