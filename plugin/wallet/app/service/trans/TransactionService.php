<?php
/**
 * @author charles
 * @created 2023/11/15 15:06
 */

namespace plugin\wallet\app\service\trans;

use charles\basic\BaseService;
use plugin\wallet\app\dao\trans\TransactionsDao;

class TransactionService extends BaseService

{
    public function __construct(TransactionsDao $dao)
    {
        $this->dao = $dao;
    }

}