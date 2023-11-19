<?php
/**
 * @author charles
 * @created 2023/10/21 16:36
 */

namespace plugin\wallet\service\block;

use IEXBase\TronAPI\Exception\TRC20Exception;
use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use plugin\wallet\contracts\BlockchainInterface;
use Web3\Eth;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;

class TronBlockchain implements BlockchainInterface
{

    protected $tron;
    private $networkId;
    protected $rpc;
    // ... 构造函数和其他私有方法

    /**
     * @throws TronException
     */
    public function __construct()
    {
        $config = config('plugin.wallet.tron');
        $headers = [
            'TRON-PRO-API-KEY' => $config['api_key'],
            'Content-Type' => 'application/json'
        ];
        $fullNode = new HttpProvider($config['full_node'], 3000, false, false, $headers);
        $solidityNode = new HttpProvider($config['solidity_node'], 3000, false, false, $headers);
        $eventServer = new HttpProvider($config['event_server'], 3000, false, false, $headers);
        $this->tron = new Tron($fullNode, $solidityNode, $eventServer); // 使用默认设置或从配置文件加载
        $rpc = new \Web3\Providers\HttpProvider(new HttpRequestManager($config['rpc_server']));
        $this->rpc = new Web3($rpc);
    }

    /**
     * @throws TronException
     */
    public function createWallet()
    {
        // 使用 Tron-API 创建新钱包
        return $this->tron->createAccount()->getRawData();

    }

    /**
     * @throws TronException
     */
    public function getBalance(string $address): float
    {
        return $this->tron->getBalance($address);
    }

    public function getAccount(string $address)
    {
        return $this->tron->getAccount($address);
    }

    /**
     * @throws TronException
     * @throws TRC20Exception
     */
    public function getBalanceContract(string $contractAddress, string $address): float
    {
        return $this->tron->contract($contractAddress)->balanceOf($address);
    }

    public function sendTransaction(string $from, string $to, float $amount, string $privateKey): string
    {
        // 在这里，你需要处理更多的逻辑，比如解锁钱包、计算手续费等

        // 这里只是一个简化的示例

        // 转换金额为 Sun（Tron 的最小单位）

    }


    public function getTransactionDetails(string $transactionId): array
    {
        // 实现获取交易细节
    }

    public function getWalletDetails(string $address): array
    {
        // 实现获取钱包细节

    }

    public function scanBlock(int $blockNumber): array
    {
        // 实现扫描区块
    }


    /**
     * @throws TronException
     */
    public function getLatestBlockNumber(): int
    {
        $blockNumber = 0;
        $error = null;
        $this->rpc->getEth()->blockNumber(function ($err, $number) use (&$blockNumber, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $blockNumber = (int)$number->toString();
            }
        });
        if ($error !== null) {
            throw new \Exception("Error: " . $error->getMessage());
        }
        return $blockNumber;

    }

    /**
     * @throws TronException
     */
    public function getBlockByNumber(int $blockHeight): array
    {
        return $this->tron->getBlock($blockHeight);


    }

    public function getBlockByHash(string $blockHash): array
    {
        // TODO: Implement getBlockByHash() method.
    }

    /**
     * @throws TronException
     */
    public function getTransactionsByAddress(string $contractAddress, string $address): array
    {
        return $this->tron->contract($contractAddress)->getTransactions($address);
    }

    public function subscribeToEvents(callable $callback)
    {
        // TODO: Implement subscribeToEvents() method.
    }

    public function sendTRX(string $from, string $to, float $amount, string $privateKey): array
    {
        $this->tron->setAddress($from);
        $this->tron->setPrivateKey($privateKey);
        return $this->tron->sendTransaction($to, $amount);
    }

    /**
     * 合约交易
     * @param string $from
     * @param string $to
     * @param float $amount
     * @param string $privateKey
     * @return string
     */
    public function sendContract(string $from, string $to, float $amount, string $privateKey): string
    {
        // TODO: Implement sendMoney() method.
    }

    public function getTron(): Tron
    {
        return $this->tron;
    }

    public function setNetworkId($networkId)
    {
        $this->networkId = $networkId;
    }

    public function getNetworkId()
    {
        return $this->networkId;
    }

    /**
     * @param array $transactions
     * @param  $address
     * @param $params
     * @return array
     */
    public function processTransaction(array $transactions, $address, $params): array
    {
        $return = [];

        foreach ($transactions as $transaction) {

            if ($transaction['ret'][0]['contractRet'] != 'SUCCESS') {
                continue;
            }

            if ($transaction['raw_data']['contract'][0]['type'] === 'TriggerSmartContract') {
                $contractData = $transaction['raw_data']['contract'][0]['parameter']['value'];

                // 检查函数签名是否是transfer
                $data = $contractData['data'];
                if (substr($data, 0, 8) === 'a9059cbb') {

                    // 获取收款人地址和金额
                    $toAddressHex = '41' . substr($data, 32, 40); // 添加41是因为Tron地址的十六进制形式以41开头
                    $amountHex = substr($data, 72, 64); // 数额在数据字段的第72到136位置
                    $toAddress = hex2bin($toAddressHex);
                    $toAddress = $this->tron->getBase58CheckAddress($toAddress); // 需要使用正确的编码函数
                    if (!in_array($toAddress, $address)) {
                        continue;
                    }
                    $ownAddress = $this->tron->getBase58CheckAddress(hex2bin($contractData['owner_address'])); // 需要使用正确的编码函数
                    $amount = hexdec($amountHex); // 这里的数额可能需要除以代币的小数位数来转换为实际数额
                    $contract = $this->tron->getBase58CheckAddress(hex2bin($contractData['contract_address']));


                    if (!in_array($contract, array_keys($params))) {
                        continue;
                    }

                    $time = date('Y-m-d H:i:s', $transaction['raw_data']['timestamp'] / 1000);
                    // 输出信息
                    echo "To Address: " . $toAddress . "\n";
                    echo "From Address: " . $ownAddress . "\n";
                    echo "Amount: " . $this->tron->fromTron($amount) . " (需要除以代币的小数位数)\n";
                    echo "Contract: " . $contract . "\n";
                    echo "time: " . $time . "\n";
                    $return[] = [
                        'to' => $toAddress,
                        'from' => $ownAddress,
                        'symbol' => $params[$contract],
                        'amount' => $this->tron->fromTron($amount),
                        'txId' => $transaction['txID'],
                        'time' => $time
                    ];
                }
            } elseif ($transaction['raw_data']['contract'][0]['type'] === 'TransferContract') {
                //处理网络原生币交易
                $contractData = $transaction['raw_data']['contract'][0]['parameter']['value'];
                $fromAddressHex = $contractData['owner_address'];
                $toAddressHex = $contractData['to_address'];
                $amount = $contractData['amount'];

                $fromAddress = $this->tron->getBase58CheckAddress(hex2bin($fromAddressHex));
                $toAddress = $this->tron->getBase58CheckAddress(hex2bin($toAddressHex));
                if (!in_array($toAddress, $address)) {
                    continue;
                }
                $time = date('Y-m-d H:i:s', $transaction['raw_data']['timestamp'] / 1000);

                // 输出信息
                echo "To Address: " . $toAddress . "\n";
                echo "From Address: " . $fromAddress . "\n";
                echo "Amount: " . $this->tron->fromTron($amount) . "\n";
                echo "Time: " . $time . "\n";

                $return[] = [
                    'to' => $toAddress,
                    'from' => $fromAddress,
                    'symbol' => $params['-'] ?? '',
                    'txId' => $transaction['txID'],
                    'amount' => $this->tron->fromTron($amount),
                    'time' => $time
                ];
            }
        }
        return $return;
    }
}