<?php
/**
 * @author charles
 * @created 2023/10/21 16:32
 */

namespace plugin\wallet\contracts;

use Illuminate\Support\Collection;

interface BlockchainInterface
{
    /**
     * 配置加载：加载特定于区块链网络的配置信息，如API端点、访问密钥等。
     * 区块获取：获取最新的区块或指定区块高度的区块。
     * 交易解析：解析区块中的交易，提取有用的信息，如发送和接收地址、交易金额等。
     * 地址匹配：将解析出来的交易与系统内的地址进行匹配。
     * 事件处理：对于匹配到的交易，执行相应的业务逻辑，如更新数据库、发送通知等。
     * 错误处理：处理在扫描过程中可能出现的错误，记录日志，以及在必要时重试。
     */
    public function createWallet() ; // 返回包含地址和私钥的数组
    public function getBalance(string $address): float;
    public function getBalanceContract(string $contractAddress, string $address): float;
    public function sendTRX(string $from, string $to, float $amount, string $privateKey): array;
    public function sendContract(string $from, string $to, float $amount, string $privateKey): string;
    public function getTransactionDetails(string $transactionId): array;
    public function getWalletDetails(string $address): array; // 获取钱包信息，比如交易历史等
    /**
     * 获取区块链的最新区块高度。
     *
     * @return int 返回最新的区块高度。
     */
    public function getLatestBlockNumber(): int;
    /**
     * 获取指定区块高度的区块信息。
     *
     * @param int $blockHeight 区块高度。
     * @return array 返回指定高度的区块信息。
     */
    public function getBlockByNumber(int $blockHeight): array;

    /**
     * 获取指定区块哈希的区块信息。
     *
     * @param string $blockHash 区块哈希。
     * @return array 返回指定哈希的区块信息。
     */
    public function getBlockByHash(string $blockHash): array;

    /**
     * 获取指定地址的所有交易。
     *
     * @param string $address 地址。
     * @return array 返回地址相关的所有交易信息。
     */
    public function getTransactionsByAddress(string $contractAddress,string $address): array;

    /**
     * 订阅实时区块或交易事件。
     *
     * @param callable $callback 回调函数，用于处理事件。
     * @return void
     */
    public function subscribeToEvents(callable $callback);
    public function setNetworkId($networkId);
    public function getNetworkId();

    /**
     * 解析交易
     * @param array $transactions
     * @return array
     */
    public function processTransaction(array $transactions, $address, $params): array;



}