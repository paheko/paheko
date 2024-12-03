<?php

namespace Paheko\Users;

use Paheko\DynamicList;
use Paheko\Users\DynamicFields;
use Paheko\Services\Services;
use Paheko\AdvancedSearch as A_S;
use Paheko\DB;
use Paheko\Utils;
use Paheko\UserException;

class AdvancedSearch extends A_S
{
	/**
	 * Returns list of columns for search
	 * @return array
	 */
	public function columns(): array
	{
		static $columns = null;

		if ($columns !== null) {
			return $columns;
		}

		$db = DB::getInstance();
		$fields = DynamicFields::getInstance();
		$identity_search_fields = $fields::getNameFieldsSearchableSQL('us');

		if (!$identity_search_fields) {
			throw new UserException('Aucun champ texte de la fiche membre n\'a été sélectionné comme identité du membre. La recherche de membre ne peut donc pas fonctionner.');
		}

		$columns = [];

		$columns['id'] = ['select' => 'u.id'];

		$order = $fields::getFirstSearchableNameField();

		if ($order === null) {
			$order = 'u.' . $db->quote($fields::getFirstNameField());
		}
		else {
			$order = 'us.' . $db->quote($order);
		}

		$order .= ' %s';

		$columns['identity'] = [
			'label'    => $fields::getNameLabel(),
			'type'     => 'text',
			'null'     => true,
			'select'   => $fields::getNameFieldsSQL('u'),
			'where'    => $identity_search_fields . ' %s',
			'order'    => $order,
		];

		$columns['number'] = [
			'label'    => 'Numéro du membre',
			'type'     => 'integer',
			'null'     => false,
			'select'   => $fields::getNumberFieldSQL('u'),
		];

		$columns['is_parent'] = [
			'label' => 'Est responsable',
			'type' => 'boolean',
			'null' => false,
			'select' => 'CASE WHEN u.is_parent = 1 THEN \'Oui\' ELSE \'Non\' END',
			'where' => 'u.is_parent %s',
		];

		$columns['is_child'] = [
			'label' => 'Est rattaché',
			'type' => 'boolean',
			'null' => false,
			'select' => 'CASE WHEN u.id_parent IS NOT NULL THEN \'Oui\' ELSE \'Non\' END',
			'where' => 'u.id_parent IS NOT NULL %s',
		];

		foreach ($fields->all() as $name => $field)
		{
			// Skip password/number as it's already in the list
			if ($field->system & $field::PASSWORD
				|| $field->system & $field::NUMBER) {
				continue;
			}

			$identifier = $db->quoteIdentifier($name);

			$column = [
				'label'  => $field->label,
				'type'   => 'text',
				'null'   => true,
				'select' => sprintf('u.%s', $identifier),
				'where'  => sprintf('%s.%s %%s', $field->hasSearchCache() ? 'us' : 'u', $identifier),
			];

			if ($fields->isText($name)) {
				$column['order'] = sprintf('%s COLLATE U_NOCASE %%s', $identifier);
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
				$column['type'] = 'date';
			}
			elseif ($field->type == 'number')
			{
				$column['type'] = 'integer';
			}
			elseif ($field->type === 'file') {
				$column['type'] = 'integer';
				$column['null'] = false;
				$column['label'] .= ' (nombres de fichiers)';
				$column['select'] = sprintf('(SELECT json_group_array(f.path) FROM users_files AS uf INNER JOIN files AS f ON f.id = uf.id_file WHERE uf.id_user = u.id AND uf.field = %s)', $db->quote($field->name));
				$column['where'] = sprintf('(SELECT COUNT(*) FROM users_files AS uf WHERE uf.id_user = u.id AND uf.field = %s) %%s', $db->quote($field->name));
			}
			elseif ($field->type === 'virtual') {
				$type = $field->getRealType();

				if ($type === 'integer' || $type === 'real') {
					$type = 'integer';
				}
				else {
					$type = 'text';
				}

				$column['type'] = $type;
				$column['null'] = $field->hasNullValues();
			}

			if ($field->type == 'tel') {
				$column['normalize'] = 'tel';
			}

			$columns[$name] = $column;
		}

		$names = $fields::getNameFields();

		if (count($names) == 1) {
			unset($columns[$names[0]]);
		}

		$columns['id_category'] = [
			'label'  => 'Catégorie',
			'type'   => 'enum',
			'null'   => false,
			'values' => $db->getAssoc('SELECT id, name FROM users_categories ORDER BY name COLLATE U_NOCASE;'),
			'select' => '(SELECT name FROM users_categories WHERE id = id_category)',
			'where'  => 'id_category %s',
		];

		$columns['date_login'] = [
			'label' => 'Date de dernière connexion',
			'type'  => 'date',
			'null'  => true,
		];

		$list = Services::listGroupedWithFeesForSelect();
		array_unshift($list, '— Aucune activité —');
		$list = array_values($list);

		$columns['subscription'] = [
			'label'  => 'Est inscrit à',
			'only_if' => 'service',
			'type'   => 'enum_restricted',
			'null'   => false,
			'values' => $list,
			'select' => 'CASE WHEN su.id IS NOT NULL THEN \'Inscrit\' ELSE \'\' END',
			'where'  => null,
			'force' => ['subscription_active', 'subscription_paid'],
		];

		$columns['subscription_active'] = [
			'label'  => 'Inscription à jour',
			'type'   => 'boolean',
			'null'   => false,
			'select' => 'CASE WHEN su.expiry_date >= date() THEN \'À jour\' ELSE \'Expirée\' END',
			'where'  => '(su.expiry_date >= date()) %s',
			'hidden' => 'true',
		];

		$columns['subscription_paid'] = [
			'label'  => 'Inscription payée',
			'type'   => 'boolean',
			'null'   => false,
			'select' => 'CASE WHEN su.paid = 1 THEN \'Payée\' ELSE \'Non payée\' END',
			'where'  => 'su.paid %s',
			'hidden' => true,
		];

		return $columns;
	}

	public function schemaTables(): array
	{
		return [
			'users' => 'Membres',
			'users_categories' => 'Catégories de membres',
			'services' => 'Activités',
			'services_fees' => 'Tarifs des activités',
			'services_users' => 'Inscriptions aux activités',
		];
	}

	public function tables(): array
	{
		return array_merge(array_keys($this->schemaTables()), [
			'users_search',
			'user_files',
			'users_view',
		]);
	}

	public function redirect(string $query, array $options = []): bool
	{
		return false;
	}

	public function redirectResult(\stdClass $result): void
	{
		Utils::redirect(sprintf('!users/details.php?id=%d', $result->id));
	}

	public function simple(string $query, array $options = []): \stdClass
	{
		$operator = 'LIKE %?%';

		if (is_numeric(trim($query)))
		{
			$column = 'number';
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
			'groups' => $query,
			'order'  => $column,
			'desc'   => false,
		];
	}

	public function make(array $query): DynamicList
	{
		$tables = 'users_view AS u INNER JOIN users_search AS us USING (id)';
		$list = $this->makeList($query, $tables, 'identity', false, ['id', 'identity']);

		$list->setExportCallback([Users::class, 'exportRowCallback']);
		return $list;
	}

	public function defaults(): \stdClass
	{
		return (object) ['groups' => [[
			'operator' => 'AND',
			'join_operator' => null,
			'conditions' => [
				[
					'column'   => 'identity',
					'operator' => 'LIKE %?%',
					'values'   => [''],
				],
			],
		]]];
	}

	public function update(array $query): array
	{
		$old_columns = [
			'service'         => ['service_', '= ?'],
			'service_not'     => ['service_', '!= ?'],
			'fee'             => ['fee_', '= ?'],
			'service_active'  => ['service_', '= ?'],
			'service_expired' => ['service_', '= ?'],
		];

		// We will look on all conditions for subscription conditions
		foreach ($query['groups'] as &$group) {
			if (empty($group['conditions'])) {
				continue;
			}

			// Migrate old service/fee conditions (TODO: remove in 1.6.0)
			// see https://fossil.kd2.org/paheko/info/c0ec40a74dc5715f
			foreach ($group['conditions'] as $key => $condition) {
				if (!isset($condition['column'], $condition['operator'], $condition['values'])) {
					continue;
				}

				$o = $old_columns[$condition['column']] ?? null;

				if (null === $o) {
					continue;
				}

				$new = [];

				foreach ($condition['values'] as $v) {
					$new[] = [
						'column' => 'subscription',
						'values' => [$o[0] . $v],
						'operator' => $o[1],
					];

					if ($operator === '= ?') {
						if ($condition['column'] === 'service_active') {
							$new[] = [
								'column' => 'subscription_active',
								'operator' => '= 1',
							];
						}
						elseif ($condition['column'] === 'service_expired') {
							$new[] = [
								'column' => 'subscription_active',
								'operator' => '= 0',
							];
						}
					}
				}

				unset($group['conditions'][$key]);
				$query['groups'][] = [
					'join_operator' => 'AND',
					'operator' => 'OR',
					'conditions' => $new,
				];
			}
		}

		unset($group);

		return $query;
	}

	/**
	 * Override parent makeList method to add specific tables for subscription joins
	 */
	public function makeList(array $query, string $tables, string $default_order, bool $default_desc, array $mandatory_columns = ['id']): DynamicList
	{
		if (!isset($query['groups']) || !is_array($query['groups'])) {
			throw new \InvalidArgumentException('Invalid JSON search object: missing groups');
		}

		$columns = $this->columns();
		$subscription_columns = ['subscription_active', 'subscription_paid'];

		$i = 0;
		$db = DB::getInstance();

		// We will look on all conditions for subscription conditions
		foreach ($query['groups'] as &$group) {
			if (empty($group['conditions'])) {
				continue;
			}

			$subscription_table = null;
			$subscription_operator = null;
			$subscription_value = null;

			foreach ($group['conditions'] as &$condition) {
				if (empty($condition['column'])) {
					continue;
				}

				$column = $columns[$condition['column']];

				// Transform subscription condition into table join
				if ($condition['column'] === 'subscription') {
					if (empty($condition['values'])) {
						throw new \InvalidArgumentException('Invalid JSON search object: missing "values" for subscription');
					}

					$subscription_table = 'su' . ($i++);
					$subscription_value = current($condition['values']);
					$type = substr($subscription_value, 0, 1) === 's' ? 'service' : 'fee';
					$id = substr($subscription_value, 1);

					$t = "\n ";

					if (empty($subscription_value)) {
						$t .= sprintf('LEFT JOIN (SELECT MAX(expiry_date), * FROM services_users GROUP BY id_user) AS %s ON %1$s.id_user = u.id', $subscription_table);

						// Invert actual operator here
						$condition['operator'] = $condition['operator'] === '= ?' ? '!= ?' : '= ?';
						$condition['label'] = 'Inscription';
					}
					elseif ($type === 'service' || $type === 'fee') {
						$t = sprintf("\n" . ' LEFT JOIN (SELECT MAX(expiry_date), * FROM services_users WHERE id_%s = %d GROUP BY id_user) AS %s ON %3$s.id_user = u.id',
							$type,
							$id,
							$subscription_table
						);

						foreach ($column['values'] as $g) {
							if (!is_array($g)) {
								continue;
							}

							foreach ($g['options'] ?? [] as $id => $label) {
								if ($id == $subscription_value) {
									if ($type === 'service') {
										$condition['label'] = $g['label'];
									}
									else {
										$condition['label'] = $g['label'] . ' — ' . $label;
									}
									break;
								}
							}
						}
					}
					else {
						throw new \InvalidArgumentException('Invalid subscription type: ' . $type);
					}

					$tables .= $t;
					$subscription_operator = $condition['operator'];
					$condition['where'] = sprintf('%s.id IS %s', $subscription_table, $condition['operator'] === '= ?' ? 'NOT NULL' : 'NULL');
					$condition['select'] = str_replace('su.', $subscription_table . '.', $columns['subscription']['select']);
				}
				// For conditions related to subscription, link them to previously selected subscription
				elseif (in_array($condition['column'], $subscription_columns)) {
					if (!$subscription_table) {
						throw new UserException(sprintf('Le critère "%s" nécessite d\'avoir également sélectionné le critère "%s" précédemment.', $column['label'], $columns['subscription']['label']));
					}

					$condition['select'] = str_replace('su.', $subscription_table . '.', $column['select']);

					if (($condition['operator'] ?? '') !== '1') {
						// You can't have paid == no if criteria is id_service != X, as this doesn't make sense
						if ($subscription_operator !== '= ?' && $subscription_value) {
							throw new UserException(sprintf('Le critère "%s" nécessite d\'avoir sélectionné "est égal à" pour le critère "%s" précédent.', $column['label'], $columns['subscription']['label']));
						}
						// You can't have paid == yes if criteria is id_service IS NULL
						elseif ($subscription_operator !== '= ?') {
							throw new UserException(sprintf('Le critère "%s" nécessite d\'avoir sélectionné "n\'est pas égal à" pour le critère "%s" précédent.', $column['label'], $columns['subscription']['label']));
						}

						$condition['where'] = str_replace('su.', $subscription_table . '.', $column['where']);
					}
				}
			}

			unset($condition);
		}

		unset($group);

		$list = parent::makeList($query, $tables, $default_order, $default_desc, $mandatory_columns);
		return $list;
	}
}
