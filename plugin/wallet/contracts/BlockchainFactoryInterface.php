<?php
/**
 * @author charles
 * @created 2023/10/21 18:07
 */

namespace plugin\wallet\contracts;

interface BlockchainFactoryInterface
{
    /**
     * 根据给定的条件创建 BlockchainInterface 的实例.
     *
     * @param array $config 配置数组，可以包含决定使用哪个区块链实现的信息
     * @return BlockchainInterface
     */
    public function make(array $config = []): BlockchainInterface;
}