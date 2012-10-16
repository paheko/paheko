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

$i = 0;

foreach ($data as $line)
{
	$line->color = ($i++ % 2) ? '#fa4' : '#9c4f15';
	$line->width = 2;
	$plot->add($line);
}

$plot->display();

?>