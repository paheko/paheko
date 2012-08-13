<?php

require_once __DIR__ . '/_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_stats.php';
$stats = new Garradin_Compta_Stats;

require_once GARRADIN_ROOT . '/include/libs/svgplot/lib.svgplot.php';

$plot = new SVGPlot(400, 300);

if (utils::get('g') == 'recettes_depenses')
{
	$data = array(
		$stats->recettes(),
		$stats->depenses(),
	);
}
elseif (utils::get('g') == 'actif_passif')
{
	$data = array(
		$stats->actif(),
		$stats->passif()
	);
}

$i = 0;

foreach ($data as $line)
{
	$line = new SVGPlot_Data($line);
	$line->color = ($i++ % 2) ? '#d98628' : '#9c4f15';
	$line->width = 5;
	$plot->add($line);
}

$plot->display();

?>