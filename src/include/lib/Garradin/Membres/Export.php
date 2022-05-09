<?php

namespace Garradin\Membres;

use Garradin\Membres;
use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\CSV;
use Garradin\CSV_Custom;
use Garradin\UserException;

class Export
{
	const TYPE_CSV = "csv";
	const TYPE_ODS = "ods";
	
	protected $champs;

	protected function export(array $list = null)
	{
		$db = DB::getInstance();

		$champs = Config::getInstance()->get('champs_membres')->getKeys();
		$champs = array_map([$db, 'quoteIdentifier'], $champs);
		$fields = 'm.' . implode(', m.', $champs);

		if ($list) {
			$list = array_map('intval', $list);
			$where = sprintf('WHERE m.%s', $db->where('id', $list));
		}
		else {
			$where = '';
		}

		$sql = sprintf('SELECT %s, c.name AS "Catégorie membre" FROM membres AS m
			INNER JOIN users_categories AS c ON m.id_category = c.id
			%s ORDER BY c.id;', $fields, $where);

		$res = $db->iterate($sql);

		return [
			array_keys((array) $res->current()),
			$res,
			sprintf('Export membres - %s - %s', Config::getInstance()->get('nom_asso'), date('Y-m-d')),
		];
	}

	public function toCSV(array $list = null): void
	{
		list($champs, $result, $name) = $this->export($list);
		CSV::toCSV($name, $result, $champs, [$this, 'exportRow']);
	}

	public function toODS(array $list = null): void
	{
		list($champs, $result, $name) = $this->export($list);
		CSV::toODS($name, $result, $champs, [$this, 'exportRow']);
	}

	public function exportRow(\stdClass $row) {
		if (null === $this->champs) {
			$this->champs = Config::getInstance()->get('champs_membres')->getAll();
		}

		foreach ($this->champs as $id => $config) {
			if (!isset($row->$id)) {
				continue;
			}

			if ($config->type == 'date') {
				$row->$id = \DateTime::createFromFormat('!Y-m-d', $row->$id);
			}
			elseif ($config->type == 'datetime') {
				$row->$id = \DateTime::createFromFormat('!Y-m-d H:i:s', $row->$id);
			}
			// convertir les champs à choix multiple de binaire vers liste séparée par des points virgules
			elseif ($config->type == 'multiple') {
				$out = [];

				foreach ($config->options as $b => $name)
				{
					if ($row->$id & (0x01 << $b)) {
						$out[] = $name;
					}
				}

				$row->$id = implode(';', $out);
			}
		}

		return $row;
	}
}
