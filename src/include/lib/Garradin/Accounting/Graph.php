<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Line;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Utils;
use Garradin\DB;
use KD2\DB\EntityManager;

use KD2\Graphics\SVG\Plot;
use KD2\Graphics\SVG\Plot_Data;

class Graph
{
	const PLOT_LINES = [
		'assets' => [
			'Banques' => ['type' => Account::TYPE_BANK],
			'Caisses' => ['type' => Account::TYPE_CASH],
			'En attente' => ['type' => Account::TYPE_OUTSTANDING],
		],
		'result' => [
			'Recettes' => ['position' => Account::REVENUE],
			'Dépenses' => ['position' => Account::EXPENSE],
		],
	];

	const PLOT_INTERVAL = 604800; // 7 days

	static public function plot(string $type, array $criterias)
	{
		if (!array_key_exists($type, self::PLOT_LINES)) {
			throw new \InvalidArgumentException('Unknown type');
		}

		$plot = new Plot(400, 300);

		$lines = self::PLOT_LINES[$type];
		$data = [];

		foreach ($lines as $label => $line_criterias) {
			$line_criterias = array_merge($criterias, $line_criterias);
			$graph = new Plot_Data(Reports::getSumsByInterval($line_criterias, self::PLOT_INTERVAL));
			$graph->title = $label;
			$data[] = $graph;
		}

		if (count($data))
		{
			/*
			$labels = [];

			foreach ($data[0]->get() as $k=>$v)
			{
				$labels[] = $k;
			}

			$plot->setLabels($labels);
			*/

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

		return $plot->output();
	}
}
