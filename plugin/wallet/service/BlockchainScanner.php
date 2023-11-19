<?php
/**
 * @author charles
 * @created 2023/11/2 18:38
 */

namespace plugin\wallet\service;

use plugin\wallet\contracts\BlockchainInterface;

class BlockchainScanner
{
    private $blockchain;
    private $networkId;

    public function __construct(BlockchainInterface $blockchain, $networkId)
    {
        $this->blockchain = $blockchain;
        $this->networkId = $networkId;
    }

    /**
     * 扫描新区块，并处理相关交易。
     */
    public function scanBlocks()
    {
        // 获取最新区块号
        $latestBlock = $this->blockchain->getLatestBlockNumber();

        // 从上一次扫描的位置开始扫描新区块
        $lastScannedBlock = $this->getLastScannedBlockNumber($this->networkId);
        for ($blockNum = $lastScannedBlock + 1; $blockNum <= $latestBlock; $blockNum++) {
            $block = $this->blockchain->getBlockByNumber($blockNum);

            // 处理区块中的每笔交易
            foreach ($block['transactions'] as $transaction) {
                $this->processTransaction($transaction);
            }

            // 更新已扫描的最新区块号
            $this->updateLastScannedBlockNumber($blockNum);
        }
    }

    private function getLastScannedBlockNumber()
    {

        // 从数据库或其他存储获取上一次扫描的区块号
    }

    private function updateLastScannedBlockNumber($blockNum)
    {
        // 更新数据库或其他存储中的最后扫描的区块号
    }

    private function processTransaction($transaction)
    {
        // 将交易与本地地址进行匹配，并执行相应的业务逻辑
    }
}