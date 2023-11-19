<?php
/**
 * @author charles
 * @created 2023/10/23 15:05
 * 各网络对应的配置跟策略
 */
return [
    'networks' => [
        'trx' => \plugin\wallet\service\block\TronBlockchain::class,
        // ... 其他网络
    ],
    'strategies'=>[
        'trx' => \plugin\wallet\strategy\TronStrategy::class,
    ],
    'scan'=>[
        'trx' => \plugin\wallet\strategy\TronStrategy::class,
    ],

];
