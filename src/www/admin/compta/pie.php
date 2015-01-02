<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if (!in_array(Utils::get('g'), ['recettes', 'depenses']))
{
	throw new UserException('Graphique inconnu.');
}

$graph = Utils::get('g');

if (Static_Cache::expired('pie_' . $graph))
{
	$stats = new Compta\Stats;
	$categories = new Compta\Categories;

	$pie = new \KD2\SVGPie(400, 250);

	if ($graph == 'recettes')
	{
		$data = $stats->repartitionRecettes();
		$categories = $categories->getList(Compta\Categories::RECETTES);
		$pie->setTitle('Répartition des recettes');
	}
	else
	{
		$data = $stats->repartitionDepenses();
		$categories = $categories->getList(Compta\Categories::DEPENSES);
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
			$others += $row['somme'];
		}
		else
		{
			$cat = $categories[$row['id_categorie']];
			$pie->add(new \KD2\SVGPie_Data($row['somme'], substr($cat['intitule'], 0, 50), $colors[$i-1]));
		}
	}

	if ($others > 0)
	{
		$pie->add(new \KD2\SVGPie_Data($others, 'Autres', '#ccc'));
	}

	Static_Cache::store('pie_' . $graph, $pie->output());
}

header('Content-Type: image/svg+xml');
Static_Cache::display('pie_' . $graph);
