<?php

namespace Garradin;

use Garradin\Entity;
use Garradin\Accounting\Years;
use Garradin\Accounting\Projects;
use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$criterias = [];

$tpl->assign('project_title', null);

if (qg('project') === 'all') {
	$criterias['projects_only'] = true;
}
elseif (qg('project')) {
	$project = Projects::get((int) qg('project'));

	if (!$project) {
		throw new UserException('Numéro de projet inconnu.');
	}

	$criterias['project'] = $project->id();
	$tpl->assign('project', $project);
	$tpl->assign('project_title', sprintf('%s - ', $project->label));
}

if (qg('year'))
{
	$year = Years::get((int) qg('year'));

	if (!$year) {
		throw new UserException('Exercice inconnu.');
	}

	if (qg('before') && ($b = Entity::filterUserDateValue(qg('before')))) {
		$criterias['before'] = $b;
	}

	if (qg('after') && ($a = Entity::filterUserDateValue(qg('after')))) {
		$criterias['after'] = $a;
	}

	$criterias['year'] = $year->id();
	$tpl->assign('year', $year);
	$tpl->assign('before_default', $criterias['before'] ?? $year->end_date);
	$tpl->assign('after_default', $criterias['after'] ?? $year->start_date);
}

if (!count($criterias))
{
	throw new UserException('Critère de rapport inconnu.');
}

if ($y2 = Years::get((int)qg('compare_year'))) {
	$tpl->assign('year2', $y2);
	$criterias['compare_year'] = $y2->id;
}

$tpl->assign('criterias', $criterias);
$criterias_query = $criterias;

foreach ($criterias_query as &$c) {
	if ($c instanceof \DateTimeInterface) {
		$c = $c->format('Y-m-d');
	}
}

$tpl->assign('criterias_query', http_build_query($criterias_query));
unset($criterias_query['compare_year']);
$tpl->assign('criterias_query_no_compare', http_build_query($criterias_query));

$tpl->assign('now', new \DateTime);