<?php
/**
 * @author charles
 * @created 2023/10/21 18:18
 */

namespace plugin\wallet\app\controller;

use support\Request;

class WalletController
{

    protected $network;
    public function __construct()
    {
        $a = config('plugin.wallet.dependence', []);
        var_dump($a);


    }
    public function test(){
        return $this->network;
    }


}