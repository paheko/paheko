<?php

namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Projects;
use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$criterias = [];

$tpl->assign('project_title', null);

if (qg('project'))
{
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

	$criterias['year'] = $year->id();
	$tpl->assign('year', $year);
	$tpl->assign('close_date', $year->closed ? $year->end_date : time());
}

if (qg('projects_only')) {
	$criterias['projects_only'] = true;
}

if (!count($criterias))
{
	throw new UserException('Critère de rapport inconnu.');
}

if ($y2 = Years::get((int)qg('compare_year'))) {
	$tpl->assign('year2', $y2);
	$criterias['compare_year'] = $y2->id;
}

$criterias_query = $criterias;
unset($criterias_query['compare_year']);

$tpl->assign('criterias', $criterias);
$tpl->assign('criterias_query', http_build_query($criterias));
$tpl->assign('criterias_query_no_compare', http_build_query($criterias_query));
