<?php

namespace Paheko;

use Paheko\Entities\Accounting\Year;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Years;
use KD2\Test;

require __DIR__ . '/_inc.php';

$chart_id = Charts::getOrInstall('fr_pca_2018');

$year = new Year;
$year->import(['id_chart' => $chart_id, 'label' => 'Test 1', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31']);
$year->save();
$id = $year->id();

$c = api('GET', sprintf('accounting/years/%d/journal', $id));
Test::isArray($c);

$c = api('POST', 'accounting/transaction', [
	'id_year' => $id,
	'label'   => 'Test recette',
	'type'    => 'revenue',
	'amount'  => '42,05',
	'debit'   => '5112',
	'credit'  => '756',
	'date'    => '01/02/2024',
]);

Test::isArray($c);
Test::hasKey('id', $c);

$a = $c['id'];

$c = api('POST', 'accounting/transaction', [
	'id_year' => $id,
	'label'   => 'Test recette 2',
	'type'    => 'revenue',
	'amount'  => '42,05',
	'debit'   => '5112',
	'credit'  => '756',
	'date'    => '01/02/2024',
	'linked_transactions' => [$a],
]);

Test::isArray($c);
Test::hasKey('id', $c);

$b = $c['id'];

$c = api('GET', sprintf('accounting/transaction/%d/transactions', $b));

Test::isArray($c);
Test::assert(count($c) === 1);
Test::assert(current($c) === $a);

$c = api('DELETE', sprintf('accounting/transaction/%d/transactions', $b));
$c = api('GET', sprintf('accounting/transaction/%d/transactions', $b));

Test::isArray($c);
Test::assert(count($c) === 0);
