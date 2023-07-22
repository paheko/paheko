<?php
namespace Paheko;

use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if ($year->closed) {
	throw new UserException('Impossible de supprimer un exercice clôturé.');
}

$form->runIf(f('delete') && f('confirm_delete'), function () use ($year) {
	$year->delete();
}, 'acc_years_delete_' . $year->id(), ADMIN_URL . 'acc/years/');

$tpl->assign('nb_transactions', $year->countTransactions());
$tpl->assign('year', $year);

$tpl->display('acc/years/delete.tpl');
