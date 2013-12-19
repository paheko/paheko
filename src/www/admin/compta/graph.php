<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if (!in_array(utils::get('g'), array('recettes_depenses', 'banques_caisses')))
{
	throw new UserException('Graphique inconnu.');
}

$graph = utils::get('g');

define('GRAPH_CACHE_DIR', GARRADIN_ROOT . '/cache/static');

if (!file_exists(GRAPH_CACHE_DIR))
{
	mkdir(GRAPH_CACHE_DIR);
}

Static_Cache::setCacheDir(GRAPH_CACHE_DIR);

if (Static_Cache::expired('graph_' . $graph))
{
	$stats = new Compta_Stats;

	require_once GARRADIN_ROOT . '/include/libs/svgplot/lib.svgplot.php';

	$plot = new \SVGPlot(400, 300);

	if ($graph == 'recettes_depenses')
	{
		$r = new \SVGPlot_Data($stats->recettes());
		$r->title = 'Recettes';

		$d = new \SVGPlot_Data($stats->depenses());
		$d->title = 'Dépenses';

		$data = array($d, $r);

		$plot->setTitle('Recettes et dépenses de l\'exercice courant');
	}
	elseif ($graph == 'banques_caisses')
	{
		$banques = new Compta_Comptes_Bancaires;

		$data = array();

		$r = new \SVGPlot_Data($stats->soldeCompte(Compta_Comptes::CAISSE));
		$r->title = 'Caisse';

		$data[] = $r;

		foreach ($banques->getList() as $banque)
		{
			$r = new \SVGPlot_Data($stats->soldeCompte($banque['id']));
			$r->title = $banque['libelle'];
			$data[] = $r;
		}

		$plot->setTitle('Solde des comptes et caisses');
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
		$colors = array('#c71', '#941', '#fa4', '#fd9', '#ffc', '#cc9');

		foreach ($data as $line)
		{
			$line->color = $colors[$i++];
			$line->width = 2;
			$plot->add($line);

			if ($i > count($colors))
				$i = 0;
		}
	}

	Static_Cache::store('graph_' . $graph, $plot->output());
}

header('Content-Type: image/svg+xml');
Static_Cache::display('graph_' . $graph);

// Clean cache sometimes
if ((time() % 100) == 0)
{
	Static_Cache::clean();
}

?>