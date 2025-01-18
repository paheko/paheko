<?php

namespace Paheko\Users;

use Paheko\DynamicList;
use Paheko\Users\DynamicFields;
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
		$db = DB::getInstance();
		$fields = DynamicFields::getInstance();
		$identity_search_fields = $fields::getNameFieldsSearchableSQL('us');

		if (!$identity_search_fields) {
			throw new UserException('Aucun champ texte de la fiche membre n\'a été sélectionné comme identité du membre. La recherche de membre ne peut donc pas fonctionner.');
		}

		$columns = [];

		$columns['id'] = [];

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
			'type'     => $fields::isNumberFieldANumber() ? 'integer' : 'text',
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
			if ($field->isPassword()
				|| $field->isNumber()) {
				continue;
			}

			// Skip fields where you don't have access
			// Note that this doesn't block access to fields using existing saved searches
			if ($this->session && !$this->session->canAccess($this->session::SECTION_USERS, $field->management_access_level)) {
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

		$columns['hidden'] = [
			'label'  => 'Membre d\'une catégorie cachée',
			'type'   => 'boolean',
			'null'   => false,
			'select' => 'CASE WHEN id_category IN (SELECT id FROM users_categories WHERE hidden = 1) THEN \'Oui\' ELSE \'Non\' END',
			'where'  => 'id_category IN (SELECT id FROM users_categories WHERE hidden = 1) %s',
		];

		$columns['service'] = [
			'label'  => 'Est inscrit à l\'activité',
			'type'   => 'enum',
			'null'   => false,
			'values' => $db->getAssoc('SELECT id, label FROM services ORDER BY label COLLATE U_NOCASE;'),
			'select' => '\'Inscrit\'',
			'where'  => 'id IN (SELECT id_user FROM services_users WHERE id_service %s)',
		];

		$columns['fee'] = [
			'label'  => 'Est inscrit au tarif',
			'type'   => 'enum',
			'null'   => false,
			'values' => $db->getAssoc('SELECT f.id, s.label || \' — \' || f.label FROM services_fees f INNER JOIN services s ON s.id = f.id_service ORDER BY s.label COLLATE U_NOCASE, f.label COLLATE U_NOCASE;'),
			'select' => '\'Inscrit\'',
			'where'  => 'id IN (SELECT id_user FROM services_users WHERE id_fee %s)',
		];

		$columns['service_not'] = [
			'label'  => 'N\'est pas inscrit à l\'activité',
			'type'   => 'enum',
			'null'   => false,
			'values' => $db->getAssoc('SELECT id, label FROM services ORDER BY label COLLATE U_NOCASE;'),
			'select' => '\'Inscrit\'',
			'where'  => 'id NOT IN (SELECT id_user FROM services_users WHERE id_service %s)',
		];

		$columns['service_active'] = [
			'label'  => 'Est à jour de l\'activité',
			'type'   => 'enum',
			'null'   => false,
			'values' => $db->getAssoc('SELECT id, label FROM services ORDER BY label COLLATE U_NOCASE;'),
			'select' => '\'À jour\'',
			'where'  => 'id IN (SELECT id_user FROM (SELECT id_user, MAX(expiry_date) AS edate FROM services_users WHERE id_service %s GROUP BY id_user) WHERE edate >= date())',
		];

		$columns['service_expired'] = [
			'label'  => 'N\'est pas à jour de l\'activité',
			'type'   => 'enum',
			'null'   => false,
			'values' => $db->getAssoc('SELECT id, label FROM services ORDER BY label COLLATE U_NOCASE;'),
			'select' => '\'Expiré\'',
			'where'  => 'id IN (SELECT id_user FROM (SELECT id_user, MAX(expiry_date) AS edate FROM services_users WHERE id_service %s GROUP BY id_user) WHERE edate < date())',
		];

		$columns['date_login'] = [
			'label' => 'Date de dernière connexion',
			'type'  => 'date',
			'null'  => true,
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

		$groups = [[
			'operator' => 'AND',
			'conditions' => [
				[
					'column'   => 'hidden',
					'operator' => '= 0',
				],
			],
		], [
			'operator' => 'OR',
			'conditions' => [
				[
					'column'   => $column,
					'operator' => $operator,
					'values'   => [$query],
				],
			],
		]];

		if (!DynamicFields::isNumberFieldANumber()) {
			$groups[0]['conditions'][] = [
				'column'   => 'number',
				'operator' => '= ?',
				'values'   => [$query],
			];
		}

		return (object) [
			'groups' => $groups,
			'order'  => $column,
			'desc'   => false,
		];
	}

	public function make(string $query): DynamicList
	{
		$tables = 'users_view AS u INNER JOIN users_search AS us USING (id)';
		$list = $this->makeList($query, $tables, 'identity', false, ['id', 'identity', 'number']);

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
}
