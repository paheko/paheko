<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Services\Fees;

require_once __DIR__ . '/../_inc.php';

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

$csrf_key = 'year_links_' . $year->id();

$form->runIf('link', function () use ($year) {
	$id = intval($_POST['target'] ?? 0);
	$target = Years::get($id);

	if (!$target) {
		throw new UserException('Invalid target year');
	}

	$changed = Fees::updateYear($year, $target);

	if (!$changed) {
		throw new UserException('L\'exercice sélectionné utilise un plan comptable différent, il n\'est pas possible de l\'utiliser pour les tarifs sélectionnés. Merci de modifier manuellement chaque tarif.');
	}
}, $csrf_key, '!acc/years/');

$fees = Fees::listByYearId($year->id());
$years = Years::listOpenAssocExcept($year->id());

$tpl->assign(compact('year', 'fees', 'years', 'csrf_key'));

$tpl->display('acc/years/links.tpl');
