<?php

use Webman\Route;
Route::group('/api/wallet',function (){
    Route::any('/get',[\plugin\wallet\app\controller\BlockchainController::class,'getAddress']);
    Route::any('/add',[\plugin\wallet\app\controller\BlockchainController::class,'createAddress']);
    Route::any('/tran',[\plugin\wallet\app\controller\BlockchainController::class,'getTransactions']);
    Route::any('/account',[\plugin\wallet\app\controller\BlockchainController::class,'getAccount']);
    Route::any('/usdt',[\plugin\wallet\app\controller\BlockchainController::class,'sendUSDT']);
    Route::any('/res',[\plugin\wallet\app\controller\BlockchainController::class,'getResource']);
    Route::any('/free',[\plugin\wallet\app\controller\BlockchainController::class,'freezebalance']);
    Route::any('/trx',[\plugin\wallet\app\controller\BlockchainController::class,'sendTRX']);
})->middleware(\app\middleware\ApiFormat::class);