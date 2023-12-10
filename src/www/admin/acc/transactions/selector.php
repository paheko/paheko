<?php
namespace Paheko;

use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$query = trim((string) (qg('q') ?? f('q')));

$list = null;

// Recherche simple
if ($query !== '') {
	$list = Transactions::quickSearch($query, (int)qg('id_year'));
}

$years = Years::listAssoc();
$tpl->assign(compact('query', 'list', 'years'));

$tpl->display('acc/transactions/selector.tpl');
