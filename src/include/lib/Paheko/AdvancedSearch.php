<?php

namespace Paheko;

use Paheko\Users\Session;

abstract class AdvancedSearch
{
	protected ?Session $session = null;

	/**
	 * From a single search string, returns a search object (stdClass) containing 3 properties:
	 * - query (array, list of search conditions)
	 * - order
	 * - desc
	 */
	abstract public function simple(string $query, array $options = []): \stdClass;

	/**
	 * Redirect to a page if the text query matches a specific text
	 * This is performed before the actual search query is built.
	 */
	abstract public function redirect(string $query, array $options = []): bool;

	/**
	 * Redirect to a page for the passed result row
	 */
	abstract public function redirectResult(\stdClass $result): void;

	/**
	 * Return list of columns. The format is similar to the one accepted in DynamicList.
	 *
	 * Those specific keys are also supported:
	 * - 'normalize' (string) will normalize the user entry to a specific format (accepted: tel, money)
	 * - 'null' (bool) if true, the user will be able to search for NULL values
	 * - 'type' (string) type of HTML input
	 */
	abstract public function columns(): array;

	/**
	 * Returns list of tables that should be documented for SQL queries
	 */
	abstract public function schemaTables(): array;

	/**
	 * Returns list of tables the user has access to for SQL queries
	 */
	abstract public function tables(): array;

	/**
	 * Builds a DynamicList object from the supplied search groups
	 */
	abstract public function make(string $query): DynamicList;

	/**
	 * Returns default empty search groups
	 */
	abstract public function defaults(): \stdClass;

	public function makeList(string $query, string $tables, string $default_order, bool $default_desc, array $mandatory_columns = ['id']): DynamicList
	{
		$query = json_decode($query, true);

		if (null === $query) {
			throw new \InvalidArgumentException('Invalid JSON search object');
		}

		$query = (object) $query;

		if (!isset($query->groups) || !is_array($query->groups)) {
			throw new \InvalidArgumentException('Invalid JSON search object: missing groups');
		}

		$conditions = $this->build($query->groups);
		array_unshift($conditions->select, $default_order); // Always include default order

		foreach ($mandatory_columns as $c) {
			if (!in_array($c, $conditions->select)) {
				array_unshift($conditions->select, $c); // Always include
			}
		}

		// Only select columns that we want
		$select_columns = array_intersect_key($this->columns(), array_flip($conditions->select));

		$order = $query->order ?? $default_order;

		if (!in_array($order, $select_columns)) {
			$order = $default_order;
		}

		DB::getInstance()->toggleUnicodeLike(true);

		$list = new DynamicList($select_columns, $tables, $conditions->where);

		$list->orderBy($order, $query->desc ?? $default_desc);
		$list->setTitle('Recherche');
		return $list;
	}

	public function build(array $groups): \stdClass
	{
		$db = DB::getInstance();
		$columns = $this->columns();

		$select_columns = [];
		$query_groups = '';
		$invalid = 0;

		foreach ($groups as $group)
		{
			if (empty($group['conditions'])
				|| empty($group['operator'])
				|| !is_array($group['conditions'])
				|| !($group['operator'] === 'AND' || $group['operator'] === 'OR'))
			{
				// Ignorer les groupes de conditions invalides
				continue;
			}

			if (isset($group['join_operator']) && $group['join_operator'] !== 'AND' && $group['join_operator'] !== 'OR') {
				continue;
			}

			$query_group_conditions = [];

			foreach ($group['conditions'] as $condition)
			{
				if (!isset($condition['column'], $condition['operator'])
					|| (isset($condition['values']) && !is_array($condition['values'])))
				{
					// Ignorer les conditions invalides
					continue;
				}

				if (!array_key_exists($condition['column'], $columns)) {
					// Ignorer une condition qui se rapporte à une colonne
					// qui n'existe pas, cas possible si on reprend une recherche
					// après avoir modifié les fiches de membres
					$invalid++;
					continue;
				}

				$select_columns[] = $condition['column'];

				// Just append the column to the select
				if ($condition['operator'] === '1') {
					continue;
				}

				$column = $columns[$condition['column']];

				if (isset($column['where'])) {
					$query = sprintf($column['where'], $condition['operator']);
				}
				else {
					$name = $column['select'] ?? $condition['column'];
					$query = sprintf('%s %s', $name, $condition['operator']);
				}

				$values = isset($condition['values']) ? $condition['values'] : [];

				if (!empty($column['normalize'])) {
					if ($column['normalize'] == 'tel') {
						// Normaliser le numéro de téléphone
						$values = array_map(['Paheko\Utils', 'normalizePhoneNumber'], $values);
					}
					elseif ($column['normalize'] == 'money') {
						$values = array_map(['Paheko\Utils', 'moneyToInteger'], $values);
					}
				}
				elseif ($column['type'] === 'integer') {
					$values = array_map('intval', $values);
				}

				// L'opérateur binaire est un peu spécial
				if ($condition['operator'] === '&' || $condition['operator'] === 'NOT &')
				{
					if (empty($values)) {
						throw new UserException('Aucun choix n\'a été sélectionné');
					}

					$new_query = [];

					$query = str_replace('NOT ', '', $query);

					foreach ($values as $value)
					{
						$new_query[] = sprintf('%s (1 << %d)', $query, (int) $value);
					}

					$query = '(' . implode(' AND ', $new_query) . ')';

					if ($condition['operator'] === 'NOT &') {
						$query = 'NOT ' . $query;
					}
				}
				// Remplacement de liste
				elseif (strpos($query, '??') !== false)
				{
					$values = array_map([$db, 'quote'], $values);
					$query = str_replace('??', implode(', ', $values), $query);
				}
				// Remplacement de recherche LIKE
				elseif (preg_match('/%\?%|%\?|\?%/', $query, $match))
				{
					$value = str_replace(['%', '_'], ['\\%', '\\_'], reset($values));
					$value = str_replace('?', $value, $match[0]);
					$query = str_replace($match[0], sprintf('%s ESCAPE \'\\\'', $db->quote($value)), $query);
				}
				// Remplacement de paramètre
				elseif (strpos($query, '?') !== false)
				{
					$expected = substr_count($query, '?');
					$found = count($values);

					if ($expected != $found)
					{
						throw new \RuntimeException(sprintf('Operator %s expects at least %d parameters, only %d supplied', $condition['operator'], $expected, $found));
					}

					for ($i = 0; $i < $expected; $i++) {
						$pos = strpos($query, '?');
						$query = substr_replace($query, $db->quote(array_shift($values)), $pos, 1);
					}
				}

				$query_group_conditions[] = $query;
			}

			if (count($query_group_conditions))
			{
				if ($query_groups !== '') {
					$query_groups .= ' ' . ($group['join_operator'] ?? 'AND') . ' ';
				}

				$query_groups.= '(' . implode(' ' . $group['operator'] . ' ', $query_group_conditions) . ')';
			}
		}

		if (!strlen($query_groups) && count($groups) && $invalid) {
			throw new UserException('Cette recherche faisait référence à des champs qui n\'existent plus.' . "\n" . 'Elle ne comporte aucun critère valide. Il vaudrait mieux la supprimer.');
		}

		return (object) [
			'select' => $select_columns,
			'where' => $query_groups ?: '1',
		];
	}

	public function setSession(?Session $session)
	{
		$this->session = $session;
	}
}