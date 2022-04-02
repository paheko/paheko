<?php

namespace Garradin\Users;

use Garradin\AdvancedSearch as A_S;

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
		$columns['id_category'] = (object) [
			'label'    => 'Catégorie',
			'type'     => 'enum',
			'null'     => false,
			'values'   => $db->getAssoc('SELECT id, name FROM users_categories ORDER BY name COLLATE U_NOCASE;'),
		];

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

			$column = (object) [
				'label'    => $field->label,
				'type'     => 'text',
				'null'     => true,
			];

			if ($fields->isText($name)) {
				$column->order = sprintf('%s COLLATE U_NOCASE %%s', $name);
			}

			if ($field->type == 'checkbox')
			{
				$column->type = 'boolean';
				$column->null = false;
			}
			elseif ($field->type == 'select')
			{
				$column->type = 'enum';
				$column->values = array_combine($field->options, $field->options);
			}
			elseif ($field->type == 'multiple')
			{
				$column->type = 'bitwise';
				$column->values = $field->options;
			}
			elseif ($field->type == 'date' || $field->type == 'datetime')
			{
				$column->type = $field->type;
			}
			elseif ($field->type == 'number')
			{
				$column->type = 'integer';
			}

			if ($field->type == 'tel') {
				$column->normalize = 'tel';
			}

			$columns[$name] = $column;
		}
	}

	public function schema(): array
	{
		$db = DB::getInstance();
		$sql = sprintf('SELECT name, sql FROM sqlite_master WHERE %s;', $db->where('name', ['users', 'users_categories']));
		return $db->getAssoc($sql);
	}

	public function simple(string $query): \stdClass
	{
		$operator = 'LIKE %?%';

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

	public function make(array $groups, string $order = 't.id', bool $desc = false): DynamicList
	{
		$tables = 'users u';
		$conditions = $this->buildConditions($groups);

		$list = new DynamicList($this->columns(), $tables, $conditions);
		$list->orderBy($order, $desc);
		return $list;
	}
}
