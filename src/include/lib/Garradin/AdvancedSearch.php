<?php

namespace Garradin;

abstract class AdvancedSearch
{
	/**
	 * From a single search string, returns a search object (stdClass) containing 3 properties:
	 * - query (array, list of search conditions)
	 * - order
	 * - desc
	 */
	abstract public function simple(string $query): array;

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
	 * Returns schema of supported tables
	 */
	abstract public function schema(): array;

	/**
	 * Builds a DynamicList object from the supplied search groups
	 */
	abstract public function make(array $groups, string $order, bool $desc): DynamicList;

	public function buildConditions(array $groups): string
	{
		$db = DB::getInstance();
		$columns = $this->columns();

		$query_columns = [];
		$query_groups = [];

		foreach ($groups as $group)
		{
			if (!isset($group['conditions'], $group['operator'])
				|| !is_array($group['conditions'])
				|| ($group['operator'] != 'AND' && $group['operator'] != 'OR'))
			{
				// Ignorer les groupes de conditions invalides
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

				if (!array_key_exists($condition['column'], $columns))
				{
					// Ignorer une condition qui se rapporte à une colonne
					// qui n'existe pas, cas possible si on reprend une recherche
					// après avoir modifié les fiches de membres
					continue;
				}

				$query_columns[] = $condition['column'];
				$column = $target_columns[$condition['column']];

				$query = sprintf('%s %s', $db->quoteIdentifier($condition['column']), $condition['operator']);

				$values = isset($condition['values']) ? $condition['values'] : [];

				if (!empty($column->normalize)) {
					if ($column->normalize == 'tel') {
						// Normaliser le numéro de téléphone
						$values = array_map(['Garradin\Utils', 'normalizePhoneNumber'], $values);
					}
					elseif ($column->normalize == 'money') {
						$values = array_map(['Garradin\Utils', 'moneyToInteger'], $values);
					}
				}

				// L'opérateur binaire est un peu spécial
				if ($condition['operator'] == '&')
				{
					$new_query = [];

					foreach ($values as $value)
					{
						$new_query[] = sprintf('%s (1 << %d)', $query, (int) $value);
					}

					$query = '(' . implode(' AND ', $new_query) . ')';
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

					for ($i = 0; $i < $expected; $i++)
					{
						$pos = strpos($query, '?');
						$query = substr_replace($query, $db->quote(array_shift($values)), $pos, 1);
					}
				}

				$query_group_conditions[] = $query;
			}

			if (count($query_group_conditions))
			{
				$query_groups[] = implode(' ' . $group['operator'] . ' ', $query_group_conditions);
			}
		}

		if (!count($query_groups))
		{
			throw new UserException('Aucune clause trouvée dans la recherche : elle contenait peut-être des clauses qui correspondent à des champs qui ont été supprimés ?');
		}

		return '(' . implode(') AND (', $query_groups) . ')';
	}
}