<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if (!in_array(utils::get('g'), array('recettes', 'depenses')))
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

if (Static_Cache::expired('graph_' . $graph) || true)
{
	$stats = new Compta_Stats;
	$categories = new Compta_Categories;

	require_once GARRADIN_ROOT . '/include/libs/svgplot/lib.svgpie.php';

	$pie = new \SVGPie(400, 250);

	if ($graph == 'recettes')
	{
		$data = $stats->repartitionRecettes();
		$categories = $categories->getList(Compta_Categories::RECETTES);
		$pie->setTitle('Répartition des recettes');
	}
	else
	{
		$data = $stats->repartitionDepenses();
		$categories = $categories->getList(Compta_Categories::DEPENSES);
		$pie->setTitle('Répartition des dépenses');
	}

	$others = 0;
	$colors = array('#c71', '#941', '#fa4', '#fd9', '#ffc', '#cc9');
	$max = count($colors);
	$i = 0;

	foreach ($data as $row)
	{
		if ($i++ >= $max)
		{
			$others += $row['nb'];
		}
		else
		{
			$cat = $categories[$row['id_categorie']];
			$pie->add(new \SVGPie_Data($row['nb'], substr($cat['intitule'], 0, 50), $colors[$i-1]));
		}
	}

	if ($others > 0)
	{
		$pie->add(new \SVGPie_Data($others, 'Autres', '#ccc'));
	}

	Static_Cache::store('graph_' . $graph, $pie->output());
}

header('Content-Type: image/svg+xml');
Static_Cache::display('graph_' . $graph);

// Clean cache sometimes
if ((time() % 100) == 0)
{
	Static_Cache::clean();
}

?>