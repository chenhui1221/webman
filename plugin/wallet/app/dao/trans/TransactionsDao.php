<?php
/**
 * @author charles
 * @created 2023/11/15 15:03
 */

namespace plugin\wallet\app\dao\trans;

use charles\basic\BaseDao;
use plugin\wallet\app\model\Transaction;

class TransactionsDao extends BaseDao
{

    protected function setModel(): string
    {
        return Transaction::class;
    }
}