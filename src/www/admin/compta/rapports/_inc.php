<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$rapports = new Compta\Rapports;
$criterias = [];

if (qg('projet'))
{
	$projets = new Compta\Projets;
	$projet = $projets->get((int) qg('projet'));

	if (!$projet)
	{
		throw new UserException('Projet inconnu.');
	}

	$criterias['id_projet'] = $projet->id;
	$tpl->assign('projet', $projet);
}
elseif (qg('exercice'))
{
	$exercices = new Compta\Exercices;

	$exercice = $exercices->get((int)qg('exercice'));

	if (!$exercice)
	{
		throw new UserException('Exercice inconnu.');
	}

	$criterias['id_exercice'] = $exercice->id;
	$tpl->assign('cloture', $exercice->cloture ? $exercice->fin : time());
	$tpl->assign('exercice', $exercice);
}
else
{
	throw new UserException('Critère de rapport inconnu.');
}
