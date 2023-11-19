<?php

namespace app\command;

use plugin\wallet\app\service\block\BlockContractService;
use plugin\wallet\app\service\block\BlockNetworkService;
use plugin\wallet\app\service\block\BlockWalletAddressService;
use plugin\wallet\factory\BlockchainFactory;
use support\Container;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


class Scan extends Command
{
    protected static $defaultName = 'scan';
    protected static $defaultDescription = 'scan';

    protected $time;

    protected $blockHeight;

    /**
     * @return void
     */
    protected function configure()
    {

        $this->addArgument('network_id', InputArgument::OPTIONAL, '区块网络id');
        $this->addArgument('block_height', InputArgument::OPTIONAL, '指定区块高度');
        $this->addArgument('run_date', InputArgument::OPTIONAL, '脚本运行时间');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $networkId = $input->getArgument('network_id') ?? 0;
        $blockHeight = $input->getArgument('network_id');

        if (!empty($blockHeight)) { //如果指定了区块高度，一般用于异常处理，手动执行某区块

        } else { //否则就是执行扫描区块

        }
        /**
         * @var $blockChain BlockchainFactory
         */
        $blockChain = Container::get(BlockchainFactory::class);
        $block = $blockChain->make(['network_id' => $networkId]);
        $height = $block->getLatestBlockNumber();
        $localHeight = 56162247;
        /**
         * @var $addressService BlockWalletAddressService
         */
        $addressService = Container::get(BlockWalletAddressService::class);
        $list = $addressService->getAddressesWithRecharge($networkId);
        /**
         * @var $contractService BlockContractService
         */
        $contractService = Container::get(BlockContractService::class);

        $symbol = $contractService->getColumn(['network_id' => $networkId], 'symbol', 'contract_address');

        $transactions = $block->getBlockByNumber($localHeight)['transactions'] ?? [];
        Log::debug('bloock:' . var_export($transactions, true));
        $result = $block->processTransaction($transactions, $list, $symbol);
        if (!empty($result)) {
            //更新本地扫描库的 高度，以及内容

        }
        var_dump($result);
        return self::SUCCESS;
    }

}
