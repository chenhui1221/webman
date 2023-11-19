<?php
/**
 * @author charles
 * @created 2023/10/21 18:20
 */

namespace plugin\wallet\factory;


use plugin\wallet\app\service\block\BlockNetworkService;
use plugin\wallet\contracts\BlockchainInterface;
use plugin\wallet\contracts\BlockchainStrategyInterface;

class BlockchainFactory
{
    protected $strategies;
    protected $networkService;

    public function __construct(BlockNetworkService $networkService)
    {
        $this->strategies = config('plugin.wallet.blockchains')['strategies'];
        $this->networkService = $networkService;
    }

    public function make(array $config): BlockchainInterface
    {
        $network = $config['network_id'] ?? 0;
        $symbol = strtolower($this->networkService->value(['id' => $network], 'symbol'));
        if (!$symbol || !isset($this->strategies[$symbol])) {
            throw new \Exception("Network strategy not found.");
        }
        /**
         * @var $strategy BlockchainStrategyInterface;
         */
        $strategy = $this->strategies[$symbol];
        return $strategy::create($config);
    }
}