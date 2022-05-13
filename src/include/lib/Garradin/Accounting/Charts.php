<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Chart;
use Garradin\Utils;
use Garradin\DB;
use KD2\DB\EntityManager;

use const Garradin\ROOT;

class Charts
{
	const BUNDLED_CHARTS = [
		'fr_pca_1999' => 'Plan comptable associatif 1999',
		'fr_pca_2018' => 'Plan comptable associatif 2020 (Règlement ANC n°2018-06)',
		'fr_pcg_2014' => 'Plan comptable général, pour entreprises (Règlement ANC n° 2014-03, consolidé 1er janvier 2019)',
		'fr_cse_2015' => 'Plan comptable des CSE (Comité Social et Économique) (Règlement ANC n°2015-01)',
		'fr_pcc_2020' => 'Plan comptable des copropriétés (2005 révisé en 2020)',
		'be_pcmn_2019' => 'Plan comptable minimum normalisé des associations et fondations 2019',
	];

	static public function install(string $chart_code): Chart
	{
		if (!array_key_exists($chart_code, self::BUNDLED_CHARTS)) {
			throw new \InvalidArgumentException('Le plan comptable demandé n\'existe pas.');
		}

		$file = sprintf('%s/include/data/charts/%s.csv', ROOT, $chart_code);

		if (!file_exists($file)) {
			throw new \LogicException('Le plan comptable demandé n\'a pas de fichier CSV');
		}

		$country = strtoupper(substr($chart_code, 0, 2));
		$code = strtoupper(substr($chart_code, 3));

		if (DB::getInstance()->test(Chart::TABLE, 'country = ? AND code = ?', $country, $code)) {
			throw new \RuntimeException('Ce plan comptable est déjà installé');
		}

		$chart = new Chart;
        $chart->label = self::BUNDLED_CHARTS[$chart_code];
        $chart->country = $country;
        $chart->code = $code;
        $chart->save();
        $chart->accounts()->importCSV($file);
        return $chart;
	}

	static public function listInstallable(): array
	{
		$installed = DB::getInstance()->getAssoc('SELECT id, LOWER(country || \'_\' || code) FROM acc_charts;');
		$out = [];

		foreach (self::BUNDLED_CHARTS as $code => $label) {
			if (in_array($code, $installed)) {
				continue;
			}

			$out[$code] = sprintf('%s — %s', Utils::getCountryName(substr($code, 0, 2)), $label);
		}

		return $out;
	}

	static public function get(int $id)
	{
		return EntityManager::findOneById(Chart::class, $id);
	}

	static public function list()
	{
		$em = EntityManager::getInstance(Chart::class);
		return $em->all('SELECT * FROM @TABLE ORDER BY country, label;');
	}

	static public function listByCountry(bool $filter_archived = false)
	{
		$where = $filter_archived ? ' AND archived = 0' : '';
		$sql = sprintf('SELECT id, country, label FROM %s WHERE 1 %s ORDER BY country, code DESC, label;', Chart::TABLE, $where);
		$list = DB::getInstance()->getGrouped($sql);
		$out = [];

		foreach ($list as $row) {
			$country = Utils::getCountryName($row->country);

			if (!array_key_exists($country, $out)) {
				$out[$country] = [];
			}

			$out[$country][$row->id] = $row->label;
		}

		return $out;
	}
}