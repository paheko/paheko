<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Year;
use KD2\DB\Date;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

$year->assertCanBeModified();

$csrf_key = 'acc_years_split_' . $year->id();

$form->runIf('split', function () use ($year) {
	$start = $_POST['start'] ?? null;
	$end = $_POST['end'] ?? null;

	$start = Entity::filterUserDateValue($start, Date::class);
	$end = Entity::filterUserDateValue($end, Date::class);

	if (!$start) {
		throw new UserException('Date de début invalide');
	}
	elseif (!$end) {
		throw new UserException('Date de fin invalide');
	}

	$target = intval($_POST['target'] ?? 0);

	if ($target) {
		$target = Years::get($target);
	}
	else {
		$target = new Year;
		$new_start = Date::createFromInterface($date);
		$new_start->modify('+1 day');
		$target->label = sprintf('Exercice %d', $date->format('Y'));
		$target->start_date = $new_start;
		$target->end_date = (clone $new_start)->modify('+1 year');
		$target->id_chart = $year->id_chart;
		$target->save();
	}

	if (!$target) {
		throw new UserException('Exercice de séparation invalide');
	}

	$year->split($start, $end, $target);
}, $csrf_key, ADMIN_URL . 'acc/years/');

$years = Years::listOpenAssocExcept($year->id());
$years = ['' => '— Créer un nouvel exercice —'] + $years;

$tpl->assign(compact('year', 'csrf_key', 'years'));

$tpl->display('acc/years/split.tpl');
