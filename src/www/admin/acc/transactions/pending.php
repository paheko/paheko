<?php
namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$list = Transactions::listPendingCreditAndDebtForClosedYears();
$list->loadFromQueryString();

$tpl->assign(compact('list'));

$tpl->display('acc/transactions/pending.tpl');
