<?php
/**
 * @author charles
 * @created 2023/10/21 18:18
 */

namespace plugin\wallet\app\controller;

use plugin\wallet\app\service\block\BlockWalletAddressService;
use plugin\wallet\factory\BlockchainFactory;
use support\Container;
use support\Log;
use support\Request;

class BlockchainController
{
    protected $blockchain;

    protected $service;
    protected $userId;
    protected $network;

    /**
     * @throws \Exception
     */
    public function __construct(BlockchainFactory $factory, Request $request, BlockWalletAddressService $service)
    {

        $network = $request->get('network', '4');
        $this->service = $service;
        $this->userId = 1001;
        $this->network = strtoupper($network);
        $this->blockchain = $factory->make(['network_id' => $this->network]);
    }

    public function getAddress(Request $request)
    {
        $addressService = Container::get(BlockWalletAddressService::class);
        $list = $addressService->getAddressesWithTypeOne();
        var_dump($list);
    }

    public function getTransactions(Request $request)
    {
        $ba = $this->blockchain->getTransactionsByAddress('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', 'TDCQe6G6KBNrYNzQT8BVbaxxMW7isTymHo');
        return app('json')->success($ba);
    }

    public function getAccount(Request $request)
    {
        $ba = $this->blockchain->getAccount('TDCQe6G6KBNrYNzQT8BVbaxxMW7isTymHo');
        return app('json')->success($ba);
    }

    public function createAddress(Request $request): \support\Response
    {

        $obj = $this->blockchain->createWallet();
        $additionalData = [
            'user_id' => $this->userId,
            'network_id' => $this->network
        ];
        $res = $this->service->addAddress(array_merge($obj, $additionalData));
        var_dump($res);
        if ($res) {
            return app('json')->success();
        }
    }

    public function sendUSDT(Request $request)
    {
        $tron = $this->blockchain->getTron();
        $tron->setAddress('TDCQe6G6KBNrYNzQT8BVbaxxMW7isTymHo');
        $tron->setPrivateKey('');
        $response = $tron->contract('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t')->transfer('TEoDf2h2FYXL83pWoiqCeDT9MwBjCgCrYQ', 5);

        Log::debug('转账USDT:' . var_export($response, true));
        return app('json')->success($response);
    }

    public function sendTRX()
    {
        $res = $this->blockchain->sendTRX('TDCQe6G6KBNrYNzQT8BVbaxxMW7isTymHo', 'TEoDf2h2FYXL83pWoiqCeDT9MwBjCgCrYQ', '0.000001', '');
        return app('json')->success($res);
    }

    public function getResource(Request $request)
    {
        $tron = $this->blockchain->getTron()->getAccountResources('TDCQe6G6KBNrYNzQT8BVbaxxMW7isTymHo');
        return app('json')->success($tron);
    }

    public function freezebalance(Request $request)
    {
        $type = strtoupper($request->get('type', 'ENERGY'));

        $tron = $this->blockchain->getTron();
        $tron->setAddress('TDCQe6G6KBNrYNzQT8BVbaxxMW7isTymHo');
        $tron->setPrivateKey('');
        $res = $tron->freezeBalance(2000.0, 15, $type);
        return app('json')->success($res);
    }

}