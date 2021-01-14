<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Line;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Utils;
use Garradin\Config;
use Garradin\DB;
use Garradin\Static_Cache;
use const Garradin\ADMIN_COLOR1;
use const Garradin\ADMIN_COLOR2;
use const Garradin\ADMIN_URL;
use KD2\DB\EntityManager;

use KD2\Graphics\SVG\Plot;
use KD2\Graphics\SVG\Plot_Data;

use KD2\Graphics\SVG\Pie;
use KD2\Graphics\SVG\Pie_Data;

class Graph
{
	const URL_LIST = [
		ADMIN_URL . 'acc/reports/graph_plot.php?type=assets&%s' => 'Évolution banques et caisses',
		ADMIN_URL . 'acc/reports/graph_plot.php?type=result&%s' => 'Évolution dépenses et recettes',
		ADMIN_URL . 'acc/reports/graph_plot.php?type=debts&%s' => 'Évolution créances (positif) et dettes (négatif)',
		ADMIN_URL . 'acc/reports/graph_pie.php?type=revenue&%s' => 'Répartition recettes',
		ADMIN_URL . 'acc/reports/graph_pie.php?type=expense&%s' => 'Répartition dépenses',
		ADMIN_URL . 'acc/reports/graph_pie.php?type=assets&%s' => 'Répartition actif',
	];

	const PLOT_TYPES = [
		'assets' => [
			'Total' => ['type' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING]],
			'Banques' => ['type' => Account::TYPE_BANK],
			'Caisses' => ['type' => Account::TYPE_CASH],
			'En attente' => ['type' => Account::TYPE_OUTSTANDING],
		],
		'result' => [
			'Recettes' => ['position' => Account::REVENUE],
			'Dépenses' => ['position' => Account::EXPENSE],
		],
		'debts' => [
			'Comptes de tiers' => ['type' => Account::TYPE_THIRD_PARTY],
		],
	];

	const PIE_TYPES = [
		'revenue' => ['position' => Account::REVENUE],
		'expense' => ['position' => Account::EXPENSE],
		'assets' => ['type' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING]],
	];

	const WEEKLY_INTERVAL = 604800; // 7 days
	const MONTHLY_INTERVAL = 2635200; // 1 month

	static public function plot(string $type, array $criterias, int $interval = self::WEEKLY_INTERVAL, int $width = 700)
	{
		if (!array_key_exists($type, self::PLOT_TYPES)) {
			throw new \InvalidArgumentException('Unknown type');
		}

		$cache_id = sha1('plot' . json_encode(func_get_args()));

		if (!Static_Cache::expired($cache_id)) {
			return Static_Cache::get($cache_id);
		}

		$plot = new Plot($width, 300);

		$lines = self::PLOT_TYPES[$type];
		$data = [];

		foreach ($lines as $label => $line_criterias) {
			$line_criterias = array_merge($criterias, $line_criterias);
			$sums = Reports::getSumsByInterval($line_criterias, $interval);

			if (count($sums) <= 1) {
				continue;
			}

			// Invert sums for banks, cash, etc.
			if ('assets' === $type || 'debts' === $type || ('result' === $type && $line_criterias['position'] == Account::EXPENSE)) {
				$sums = array_map(function ($v) { return $v * -1; }, $sums);
			}

			$sums = array_map(function ($v) { return (int)$v/100; }, $sums);

			$graph = new Plot_Data($sums);
			$graph->title = $label;
			$data[] = $graph;
		}


		if (count($data))
		{
			$labels = [];

			foreach ($data[0]->get() as $k=>$v)
			{
				$date = new \DateTime('@' . ($k * $interval));
				$labels[] = Utils::date_fr($date, 'M y');
			}

			$plot->setLabels($labels);

			$i = 0;
			$colors = self::getColors();

			foreach ($data as $line)
			{
				$line->color = $colors[$i++];
				$line->width = 3;
				$plot->add($line);

				if ($i >= count($colors))
					$i = 0;
			}
		}

		$out = $plot->output();

		Static_Cache::store($cache_id, $out);

		return $out;
	}

	static public function pie(string $type, array $criterias)
	{
		if (!array_key_exists($type, self::PIE_TYPES)) {
			throw new \InvalidArgumentException('Unknown type');
		}

		$cache_id = sha1('pie' . json_encode(func_get_args()));

		if (!Static_Cache::expired($cache_id)) {
			return Static_Cache::get($cache_id);
		}

		$pie = new Pie(700, 300);

		$pie_criterias = self::PIE_TYPES[$type];
		$data = Reports::getClosingSumsWithAccounts(array_merge($criterias, $pie_criterias), 'ABS(sum) DESC');

		$others = 0;
		$colors = self::getColors();
		$max = count($colors);
		$total = 0;
		$count = 0;
		$i = 0;

		foreach ($data as $row) {
			$row->sum = abs($row->sum);
			$total += $row->sum;
		}

		foreach ($data as $row)
		{
			if ($i++ >= $max || $count > $total*0.95)
			{
				$others += $row->sum;
			}
			else
			{
				$label = strlen($row->label) > 40 ? substr($row->label, 0, 38) . '…' : $row->label;
				$pie->add(new Pie_Data(abs($row->sum) / 100, $label, $colors[$i-1]));
			}

			$count += $row->sum;
		}

		if ($others != 0)
		{
			$pie->add(new Pie_Data(abs($others) / 100, 'Autres', '#ccc'));
		}

		$pie->togglePercentage(true);

		$out = $pie->output();

		Static_Cache::store($cache_id, $out);

		return $out;
	}

	static protected function getColors()
	{
		$config = Config::getInstance();
		$c1 = $config->get('couleur1') ?: ADMIN_COLOR1;
		$c2 = $config->get('couleur2') ?: ADMIN_COLOR2;
		list($h, $s, $v) = Utils::rgbToHsv($c1);
		list($h1, $s, $v) = Utils::rgbToHsv($c2);

		$colors = [];

		for ($i = 0; $i < 6; $i++) {
			if ($i % 2 == 0) {
				$s = $v = 50;
				$h =& $h1;
			}
			else {
				$s = $v = 70;
				$h =& $h2;
			}

			$colors[] = sprintf('hsl(%d, %d%%, %d%%)', $h, $s, $v);

			$h += 30;

			if ($h > 360) {
				$h -= 360;
			}
		}

		return $colors;
	}
}
