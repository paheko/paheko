<?php

namespace Garradin\Users;

use Garradin\DynamicList;
use Garradin\Users\DynamicFields;
use Garradin\AdvancedSearch as A_S;
use Garradin\DB;

class AdvancedSearch extends A_S
{
	/**
	 * Returns list of columns for search
	 * @return array
	 */
	public function columns(): array
	{
		$db = DB::getInstance();
		$fields = DynamicFields::getInstance();

		$columns = [];

		/*
		$columns['identity'] = (object) [
			'label'    => $fields::getNameLabel(),
			'type'     => 'text',
			'null'     => true,
			'select'   => $fields::getNameFieldsSQL(),
			'order'    => sprintf('%s COLLATE U_NOCASE %%s', current($fields::getNameFields())),
		];
		*/

		foreach ($fields->all() as $name => $field)
		{
			/*
			// already included in identity
			if ($field->system & $field::NAME) {
				continue;
			}
			*/

			// nope
			if ($field->system & $field::PASSWORD) {
				continue;
			}

			$column = [
				'label'    => $field->label,
				'type'     => 'text',
				'null'     => true,
			];

			if ($fields->isText($name)) {
				$column['order'] = sprintf('%s COLLATE U_NOCASE %%s', $name);
			}

			if ($field->type == 'checkbox')
			{
				$column['type'] = 'boolean';
				$column['null'] = false;
			}
			elseif ($field->type == 'select')
			{
				$column['type'] = 'enum';
				$column['values'] = array_combine($field->options, $field->options);
			}
			elseif ($field->type == 'multiple')
			{
				$column['type'] = 'bitwise';
				$column['values'] = $field->options;
			}
			elseif ($field->type == 'date' || $field->type == 'datetime')
			{
				$column['type'] = $field->type;
			}
			elseif ($field->type == 'number')
			{
				$column['type'] = 'integer';
			}

			if ($field->type == 'tel') {
				$column['normalize'] = 'tel';
			}

			$columns[$name] = $column;
		}

		$columns['id_category'] = [
			'label'    => 'Catégorie',
			'type'     => 'enum',
			'null'     => false,
			'values'   => $db->getAssoc('SELECT id, name FROM users_categories ORDER BY name COLLATE U_NOCASE;'),
		];

		return $columns;
	}

	public function schema(): array
	{
		$db = DB::getInstance();
		$sql = sprintf('SELECT name, sql FROM sqlite_master WHERE %s ORDER BY name;', $db->where('name', ['users', 'users_categories']));
		return $db->getAssoc($sql);
	}

	public function simple(string $query): \stdClass
	{
		$operator = 'LIKE %?%';
		$db = DB::getInstance();

		if (is_numeric(trim($query)))
		{
			$column = DynamicFields::getNumberField();
			$operator = '= ?';
		}
		elseif (strpos($query, '@') !== false)
		{
			$column = DynamicFields::getFirstEmailField();
		}
		else
		{
			$column = 'identity';
		}

		// Try to redirect to user if there is only one user
		if ($operator == '= ?') {
			$sql = sprintf('SELECT id, COUNT(*) AS count FROM users WHERE %s = ?;', $column);
			$single_query = (int) $query;
		}
		else {
			$sql = sprintf('SELECT id, COUNT(*) AS count FROM users WHERE %s LIKE ?;', $column);
			$single_query = '%' . trim($query) . '%';
		}

		if (($row = $db->first($sql, $single_query)) && $row->count == 1) {
			Utils::redirect('!users/details.php?id=' . $id);
		}

		$query = [[
			'operator' => 'AND',
			'conditions' => [
				[
					'column'   => $column,
					'operator' => $operator,
					'values'   => [$query],
				],
			],
		]];

		return (object) [
			'query' => $query,
			'order' => $column,
			'desc'  => false,
		];
	}

	public function make(string $query): DynamicList
	{
		$tables = 'users u';
		return $this->makeList($query, $tables, current(DynamicFields::getNameFields()), false);
	}

	public function defaults(): \stdClass
	{
		return (object) ['groups' => [[
			'operator' => 'AND',
			'conditions' => [
				[
					'column'   => current(DynamicFields::getNameFields()),
					'operator' => 'LIKE %?%',
					'values'   => [''],
				],
			],
		]]];
	}
}
