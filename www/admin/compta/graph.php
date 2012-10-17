<?php

require_once __DIR__ . '/_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_stats.php';
$stats = new Garradin_Compta_Stats;

require_once GARRADIN_ROOT . '/include/libs/svgplot/lib.svgplot.php';

$plot = new SVGPlot(400, 300);

if (utils::get('g') == 'recettes_depenses')
{
	$r = new SVGPlot_Data($stats->recettes());
	$r->title = 'Recettes';

	$d = new SVGPlot_Data($stats->depenses());
	$d->title = 'Dépenses';

	$data = array($r, $d);

	$plot->setTitle('Recettes et dépenses de l\'exercice courant');

	$labels = array();

	foreach ($r->get() as $k=>$v)
	{
		$labels[] = utils::date_fr('M y', strtotime(substr($k, 0, 4) . '-' . substr($k, 4, 2) .'-01'));
	}

	$plot->setLabels($labels);
}
elseif (utils::get('g') == 'banques_caisses')
{
	require_once GARRADIN_ROOT . '/include/class.compta_comptes_bancaires.php';
	$banques = new Garradin_Compta_Comptes_Bancaires;

	$data = array();

	$r = new SVGPlot_Data($stats->soldeCompte(Garradin_Compta_Comptes::CAISSE));
	$r->title = 'Caisse';

	$data[] = $r;

	foreach ($banques->getList() as $banque)
	{
		$r = new SVGPlot_Data($stats->soldeCompte($banque['id']));
		$r->title = $banque['libelle'];
		$data[] = $r;
	}

	$plot->setTitle('Solde des comptes et caisses');
}
elseif (utils::get('g') == 'dettes')
{
	$data = array();

	$r = new SVGPlot_Data($stats->soldeCompte('401%', 'credit', 'debit'));
	$r->title = 'Dettes fournisseurs';
	$data[] = $r;

	$r = new SVGPlot_Data($stats->soldeCompte('411%', 'credit', 'debit'));
	$r->title = 'Dettes usagers';
	$data[] = $r;

	$plot->setTitle('Dettes');
}

if (!empty($data))
{
	$labels = array();

	foreach ($data[0]->get() as $k=>$v)
	{
		$labels[] = utils::date_fr('M y', strtotime(substr($k, 0, 4) . '-' . substr($k, 4, 2) .'-01'));
	}

	$plot->setLabels($labels);

	$i = 0;
	$colors = array('#fa4', '#941', '#af4', '#4af', '#a4f');

	foreach ($data as $line)
	{
		$line->color = $colors[$i++];
		$line->width = 2;
		$plot->add($line);

		if ($i > count($colors))
			$i = 0;
	}
}

$plot->display();

?>