<?php
/**
 * @author charles
 * @created 2023/11/10 18:01
 */

namespace plugin\wallet\app\service;

use charles\basic\BaseService;
use plugin\wallet\app\dao\block\BlockScanNumberLogDao;
use plugin\wallet\contracts\BlockchainInterface;
use support\Container;

class BlockScan extends BaseService
{
    /**
     * @var BlockchainInterface;
     */
    protected $blockchainService;
    protected $delay = 0; //延迟高度

    public function __construct(BlockScanNumberLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function chain(BlockchainInterface $network)
    {
        $this->blockchainService = $network;
    }

    public function scanBlockchain(array $address)
    {
        // 获取最新区块号
        $latestBlock = $this->blockchainService->getLatestBlockNumber();

        $networkId = $this->blockchainService->getNetworkId();
        // 从上一次扫描的位置开始扫描新区块
        $lastScannedBlock = $this->getLastScannedBlockNumber($networkId);
        $lastScannedBlock == 0 && $latestBlock = $latestBlock - $this->delay - 1;
        for ($blockNum = $lastScannedBlock + 1; $blockNum <= $latestBlock - $this->delay; $blockNum++) {
            $block = $this->blockchainService->getBlockByNumber($blockNum);

            // 处理区块中的每笔交易
            foreach ($block['transactions'] as $transaction) {
                $this->processTransaction($transaction, $address);
            }

            // 更新已扫描的最新区块号
            $this->updateLastScannedBlockNumber($blockNum);
            return;
        }
    }

    public function getLastScannedBlockNumber($networkId): int
    {
        return $this->dao->value(['network_id' => $networkId], 'local_block_height') ?? 0;
    }

    public function updateLastScannedBlockNumber($blockNum)
    {

    }

    /**
     * 处理交易
     * @param $transaction
     * @param array $address
     * @param array $params  //symbol=trx,contractAddress
     * @return void
     */
    public function processTransaction($transactions, array $address = [], array $params = [])
    {
        $this->blockchainService->processTransaction($transactions, $address, $params);
        $address =[
            'contractAddress'=>'',
            'symbol'=>'',
            'address'=>'',
        ];

       //获取USDT转账记录
        $usdtContractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; // 需要用实际的USDT TRC20合约地址替换
        foreach ($transactions as $transaction) {
            if ($transaction['raw_data']['contract'][0]['type'] === 'TriggerSmartContract') {
                $contractData = $transaction['raw_data']['contract'][0]['parameter']['value'];

                // 检查是否是USDT合约
                /* if ($this->tron->getBase58CheckAddress($contractData['contract_address']) === $usdtContractAddress) {*/
                // 检查函数签名是否是transfer
                $data = $contractData['data'];
                if (substr($data, 0, 8) === 'a9059cbb') {
                    // 获取收款人地址和金额
                    $toAddressHex = '41' . substr($data, 32, 40); // 添加41是因为Tron地址的十六进制形式以41开头
                    $amountHex = substr($data, 72, 64); // 数额在数据字段的第72到136位置
                    $toAddress = hex2bin($toAddressHex);
                    $toAddress = $this->tron->getBase58CheckAddress($toAddress); // 需要使用正确的编码函数
                    $amount = hexdec($amountHex); // 这里的数额可能需要除以代币的小数位数来转换为实际数额
                    $contract = $this->tron->getBase58CheckAddress(hex2bin($contractData['contract_address']));
                    // 输出信息
                    echo "To Address: " . $toAddress . "\n";
                    echo "Amount: " . $amount . " (需要除以代币的小数位数)\n";
                    echo "Amount: " . $contract . "\n";
                    // TODO: 进一步处理TRC20代币转账信息
                }
            }
            //  }
        }
    }

}