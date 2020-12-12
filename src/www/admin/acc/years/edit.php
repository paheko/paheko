<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Entities\Accounting\Year;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

$csrf_key = 'acc_years_edit_' . $year->id();

$form->runIf('edit', function () use ($year) {
	if (f('split')) {
		$date = \DateTime::createFromFormat('!d/m/Y', f('end_date'));

		if (!$date) {
			throw new UserException('Date de séparation invalide');
		}

		$target = f('split_year');

		if ($target) {
			$target = Years::get($target);
		}
		else {
			$target = new Year;
			$new_start = (clone $date)->modify('+1 day');
	        $target->label = sprintf('Exercice %d', $date->format('Y'));
	        $target->start_date = $new_start;
	        $target->end_date = (clone $new_start)->modify('+1 year');
	        $target->id_chart = $year->id_chart;
	        $target->save();
		}

		if (!$target) {
			throw new UserException('Exercice de séparation invalide');
		}

		$year->split($date, $target);
	}

	$year->importForm();
	$year->save();
}, $csrf_key, ADMIN_URL . 'acc/years/');

$tpl->assign(compact('year', 'csrf_key'));
$tpl->assign('split_years', ['' => '-- Créer un nouvel exercice'] + Years::listOpenAssocExcept($year->id()));

$tpl->display('acc/years/edit.tpl');
