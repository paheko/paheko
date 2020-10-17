<?php
namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$years = new Years;

$graphs = [
	ADMIN_URL . 'acc/reports/graph_plot.php?type=assets&year=%s' => 'Évolution banques et caisses',
	ADMIN_URL . 'acc/reports/graph_plot.php?type=result&year=%s' => 'Évolution dépenses et recettes',
	ADMIN_URL . 'acc/reports/graph_plot.php?type=debts&year=%s' => 'Évolution dettes et créances',
	ADMIN_URL . 'acc/reports/graph_pie.php?type=revenue&year=%s' => 'Répartition recettes',
	ADMIN_URL . 'acc/reports/graph_pie.php?type=expense&year=%s' => 'Répartition dépenses',
	ADMIN_URL . 'acc/reports/graph_pie.php?type=assets&year=%s' => 'Répartition actif',
];

$tpl->assign('graphs', $graphs);

$tpl->assign('years', $years->listOpen());

$tpl->display('acc/index.tpl');
