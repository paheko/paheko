<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if (!in_array(utils::get('g'), ['recettes', 'depenses']))
{
	throw new UserException('Graphique inconnu.');
}

$graph = utils::get('g');

if (Static_Cache::expired('pie_' . $graph))
{
	$stats = new Compta_Stats;
	$categories = new Compta_Categories;

	require_once ROOT . '/include/libs/svgplot/lib.svgpie.php';

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
	$colors = ['#c71', '#941', '#fa4', '#fd9', '#ffc', '#cc9'];
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

	Static_Cache::store('pie_' . $graph, $pie->output());
}

header('Content-Type: image/svg+xml');
Static_Cache::display('pie_' . $graph);
