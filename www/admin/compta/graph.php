<?php

require_once __DIR__ . '/_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

require_once GARRADIN_ROOT . '/include/libs/svgplot/lib.svgplot.php';

$plot = new SVGPlot(400, 300);

if (utils::get('g') == 'recettes_depenses')
{
	$data = array(
		$journal->getStatsRecettes(),
		//$journal->getStatsDepenses(),
	);
}

foreach ($data as $line)
{
	$line = new SVGPlot_Data($line);
	$line->color = '#d98628';
	$line->width = 5;
	$plot->add($line);
}

$plot->display();

?>