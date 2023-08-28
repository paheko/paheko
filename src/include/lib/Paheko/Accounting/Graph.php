<?php

namespace Paheko\Accounting;

use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Line;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Utils;
use Paheko\Config;
use Paheko\DB;
use Paheko\UserTemplate\CommonModifiers;

use const Paheko\ADMIN_COLOR1;
use const Paheko\ADMIN_COLOR2;
use const Paheko\ADMIN_URL;

use KD2\DB\EntityManager;

use KD2\Graphics\SVG\Plot;
use KD2\Graphics\SVG\Plot_Data;

use KD2\Graphics\SVG\Pie;
use KD2\Graphics\SVG\Pie_Data;

use KD2\Graphics\SVG\Bar;
use KD2\Graphics\SVG\Bar_Data_Set;

class Graph
{
	const URL_LIST = [
		ADMIN_URL . 'acc/reports/graph_plot.php?type=assets&%s' => 'Évolution banques et caisses',
		ADMIN_URL . 'acc/reports/graph_plot.php?type=result&%s' => 'Évolution dépenses et recettes',
		ADMIN_URL . 'acc/reports/graph_plot.php?type=debts&%s' => 'Évolution créances (positif) et dettes (négatif)',
		ADMIN_URL . 'acc/reports/graph_pie.php?type=assets&%s' => 'Répartition actif',
		ADMIN_URL . 'acc/reports/graph_pie.php?type=revenue&%s' => 'Répartition recettes',
		ADMIN_URL . 'acc/reports/graph_pie.php?type=expense&%s' => 'Répartition dépenses',
	];

	const PLOT_TYPES = [
		'assets' => [
			'Total' => ['type' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING], 'exclude_position' => [Account::LIABILITY]],
			'Banques' => ['type' => Account::TYPE_BANK],
			'Caisses' => ['type' => Account::TYPE_CASH],
			'En attente' => ['type' => Account::TYPE_OUTSTANDING, 'exclude_position' => [Account::LIABILITY]],
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
		'revenue' => ['position' => Account::REVENUE, 'exclude_type' => Account::TYPE_VOLUNTEERING_REVENUE],
		'expense' => ['position' => Account::EXPENSE, 'exclude_type' => Account::TYPE_VOLUNTEERING_EXPENSE],
		'assets' => ['type' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING]],
	];

	const WEEKLY_INTERVAL = 604800; // 7 days
	const MONTHLY_INTERVAL = 2635200; // 1 month

	static public function plot(string $type, array $criterias, int $interval = self::WEEKLY_INTERVAL, int $width = 700)
	{
		if (!array_key_exists($type, self::PLOT_TYPES)) {
			throw new \InvalidArgumentException('Unknown type');
		}

		$plot = new Plot($width, 300);

		if ($type === 'result') {
			$plot->setLegendPosition($plot::POSITION_BOTTOM_RIGHT);
		}

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

		return $out;
	}

	static public function pie(string $type, array $criterias)
	{
		if (!array_key_exists($type, self::PIE_TYPES)) {
			throw new \InvalidArgumentException('Unknown type');
		}

		$pie = new Pie(700, 300);

		$pie_criterias = self::PIE_TYPES[$type];
		$data = Reports::getAccountsBalances(array_merge($criterias, $pie_criterias), 'balance DESC');

		$others = 0;
		$colors = self::getColors();
		$max = count($colors);
		$total = 0;
		$count = 0;
		$i = 0;

		$currency = Config::getInstance()->monnaie;

		foreach ($data as $row) {
			$total += $row->balance;
		}

		foreach ($data as $row)
		{
			if ($i++ >= $max || $count > $total*0.95)
			{
				$others += $row->balance;
			}
			else
			{
				$label = strlen($row->label) > 40 ? trim(substr($row->label, 0, 38)) . '…' : $row->label;
				$data = new Pie_Data(abs($row->balance) / 100, $label, $colors[$i-1]);
				$data->sublabel = Utils::money_format(intval($row->balance / 100) * 100, null, ' ', true) . ' ' . $currency;
				$pie->add($data);
			}

			$count += $row->balance;
		}

		if ($others != 0)
		{
			$data = new Pie_Data(abs($others) / 100, 'Autres', '#ccc');
			$data->sublabel = Utils::money_format(intval($others / 100) * 100, null, ' ', true) . ' ' . $currency;
			$pie->add($data);
		}

		$pie->togglePercentage(true);

		$out = $pie->output();

		return $out;
	}

	static public function bar(string $type, array $criterias)
	{
		if (!array_key_exists($type, self::PLOT_TYPES)) {
			throw new \InvalidArgumentException('Unknown type');
		}

		$bar = new Bar(600, 300);

		$lines = self::PLOT_TYPES[$type];
		$data = [];

		$colors = self::getColors();

		foreach ($lines as $label => $line_criterias) {
			$color = current($colors);
			next($colors);

			$line_criterias = array_merge($criterias, $line_criterias);
			$years = Reports::getSumsPerYear($line_criterias);

			if (count($years) < 1) {
				continue;
			}

			foreach ($years as $year) {
				$start = Utils::date_fr($year->start_date, 'Y');
				$end = Utils::date_fr($year->end_date, 'Y');
				$year_label = $start == $end ? $start : sprintf('%s-%s', $start, substr($end, -2));

				$year_id = $year_label . '-' . $year->id;

				if (!isset($data[$year_id])) {
					$data[$year_id] = new Bar_Data_Set($year_label);
				}

				$data[$year_id]->add((int) $year->balance / 100, $label, $color);
			}
		}

		ksort($data);

		foreach ($data as $group) {
			$bar->add($group);
		}

		$out = $bar->output();

		return $out;
	}

	static protected function getColors()
	{
		$config = Config::getInstance();
		$c1 = $config->get('color1') ?: ADMIN_COLOR1;
		$c2 = $config->get('color2') ?: ADMIN_COLOR2;
		list($h, $s, $v) = Utils::rgbToHsv($c1);
		list($h1, $s, $v) = Utils::rgbToHsv($c2);

		$colors = [];

		for ($i = 0; $i < 5; $i++) {
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
