<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if (!in_array(Utils::get('g'), ['recettes_depenses', 'banques_caisses']))
{
	throw new UserException('Graphique inconnu.');
}

$graph = Utils::get('g');

if (Static_Cache::expired('graph_' . $graph))
{
	$stats = new Compta\Stats;

	$plot = new \KD2\SVGPlot(400, 300);

	if ($graph == 'recettes_depenses')
	{
		$r = new \KD2\SVGPlot_Data($stats->recettes());
		$r->title = 'Recettes';

		$d = new \KD2\SVGPlot_Data($stats->depenses());
		$d->title = 'Dépenses';

		$data = [$d, $r];

		$plot->setTitle('Recettes et dépenses de l\'exercice courant');
	}
	elseif ($graph == 'banques_caisses')
	{
		$banques = new Compta\Comptes_Bancaires;

		$data = [];

		$r = new \KD2\SVGPlot_Data($stats->soldeCompte(Compta\Comptes::CAISSE));
		$r->title = 'Caisse';

		$data[] = $r;

		foreach ($banques->getList() as $banque)
		{
			$r = new \KD2\SVGPlot_Data($stats->soldeCompte($banque['id']));
			$r->title = $banque['libelle'];
			$data[] = $r;
		}

		$plot->setTitle('Solde des comptes et caisses');
	}

	if (!empty($data))
	{
		$labels = [];

		foreach ($data[0]->get() as $k=>$v)
		{
			$labels[] = Utils::date_fr('M y', strtotime(substr($k, 0, 4) . '-' . substr($k, 4, 2) .'-01'));
		}

		$plot->setLabels($labels);

		$i = 0;
		$colors = ['#c71', '#941', '#fa4', '#fd9', '#ffc', '#cc9'];

		foreach ($data as $line)
		{
			$line->color = $colors[$i++];
			$line->width = 2;
			$plot->add($line);

			if ($i >= count($colors))
				$i = 0;
		}
	}

	Static_Cache::store('graph_' . $graph, $plot->output());
}

header('Content-Type: image/svg+xml');
Static_Cache::display('graph_' . $graph);
