<?php
declare(strict_types=1);

namespace Garradin\Users;

use Garradin\Entities\Users\User;

use Garradin\Config;
use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Search;
use Garradin\Utils;
use Garradin\UserException;

use KD2\SMTP;
use KD2\DB\EntityManager as EM;

class Users
{
	static public function iterateAssocByCategory(?int $id_category = null): iterable
	{
		$where = $id_category ? sprintf('id_category = %d', $id_category) : 'id_category IN (SELECT id FROM users_categories WHERE hidden = 0)';

		$sql = sprintf('SELECT id, %s AS name FROM users WHERE %s ORDER BY name COLLATE U_NOCASE;',
			DynamicFields::getNameFieldsSQL(),
			$where);

		foreach (DB::getInstance()->iterate($sql) as $row) {
			yield $row->id => $row->name;
		}
	}

	/**
	 * Return a list for all emails by category
	 * @param  int|null $id_category If NULL, then all categories except hidden ones will be returned
	 */
	static public function iterateEmailsByCategory(?int $id_category = null): iterable
	{
		$db = DB::getInstance();
		$fields = DynamicFields::getEmailFields();
		$sql = [];
		$where = $id_category ? sprintf('id_category = %d', $id_category) : 'id_category IN (SELECT id FROM users_categories WHERE hidden = 0)';

		foreach ($fields as $field) {
			$sql[] = sprintf('SELECT *, %s AS _email FROM users WHERE %s AND %1$s IS NOT NULL', $db->quoteIdentifier($field), $where);
		}

		return $db->iterate(implode(' UNION ALL ', $sql));
	}

	/**
	 * Return a list of all emails by service (user must be active)
	 */
	static public function iterateEmailsByActiveService(int $id_service): iterable
	{
		$db = DB::getInstance();

		// Create a temporary table
		if (!$db->test('sqlite_temp_master', 'type = \'table\' AND name=\'users_active_services\'')) {
			$db->exec('DROP TABLE IF EXISTS users_active_services;
				CREATE TEMPORARY TABLE IF NOT EXISTS users_active_services (id, service);
				INSERT INTO users_active_services SELECT id_user, id_service FROM (
					SELECT id_user, id_service, MAX(expiry_date) FROM services_users
					WHERE expiry_date IS NULL OR expiry_date >= date()
					GROUP BY id_user, id_service
				);
				DELETE FROM users_active_services WHERE id IN (SELECT id FROM users WHERE id_category IN (SELECT id FROM users_categories WHERE hidden =1));');
		}

		$fields = DynamicFields::getEmailFields();
		$sql = [];

		foreach ($fields as $field) {
			$sql[] = sprintf('SELECT u.*, u.%s AS _email FROM users u INNER JOIN users_active_services s ON s.id = u.id
				WHERE s.service = %d AND %1$s IS NOT NULL', $db->quoteIdentifier($field), $id_service);
		}

		return $db->iterate(implode(' UNION ALL ', $sql));
	}

	static public function iterateEmailsBySearch(int $id_search): iterable
	{
		$db = DB::getInstance();

		$s = Search::get($id_search);
		// Make sure the query is protected and safe, by doing a protectSelect
		$s->query(['limit' => 1]);

		$header = $s->getHeader();
		$id_column = null;

		if (in_array('id', $header)) {
			$id_column = 'id';
		}
		elseif (in_array('_user_id', $header)) {
			$id_column = '_user_id';
		}
		else {
			throw new UserException('La recherche ne comporte pas de colonne "id" ou "_user_id", et donc ne permet pas l\'envoi d\'email.');
		}

		// We only need the user id, store it in a temporary table for now
		$db->exec('DROP TABLE IF EXISTS users_tmp_search; CREATE TEMPORARY TABLE IF NOT EXISTS users_tmp_search (id);');
		$db->exec(sprintf('INSERT INTO users_tmp_search SELECT %s FROM (%s)', $id_column, $s->SQL()));

		$fields = DynamicFields::getEmailFields();

		$sql = [];

		foreach ($fields as $field) {
			$sql[] = sprintf('SELECT u.*, u.%s AS _email FROM users u INNER JOIN users_tmp_search AS s ON s.id = u.id', $db->quoteIdentifier($field));
		}

		return $db->iterate(implode(' UNION ALL ', $sql));
	}

	static public function listByCategory(?int $id_category = null): DynamicList
	{
		$df = DynamicFields::getInstance();

		$columns = [
			'_user_id' => [
				'select' => 'id',
			],
		];

		$default_columns = [
			'number' => [
				'label' => 'Num.',
				'select' => $df->getNumberField(),
			],
			'identity' => [
				'label' => $df->getNameLabel(),
				'select' => $df->getNameFieldsSQL(),
			]
		];

		$fields = $df->getListedFields();

		foreach ($fields as $key => $config) {
			if (isset($default_columns[$key])) {
				$columns[$key] = $default_columns[$key];
				continue;
			}

			$columns[$key] = [
				'label' => $config->label,
			];
		}

		foreach ($default_columns as $key => $config) {
			if (!isset($columns[$key])) {
				$columns[$key] = $config;
			}
		}

		$tables = User::TABLE;

		if (!$id_category) {
			$conditions = sprintf('id_category IN (SELECT id FROM users_categories WHERE hidden = 0)');
		}
		elseif ($id_category > 0) {
			$conditions = sprintf('id_category = %d', $id_category);
		}
		else {
			$conditions = '1';
		}

		$order = 'identity';

		if (!isset($columns[$order])) {
			$order = key($fields) ?? 'number';
		}

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy($order, false);

		return $list;
	}

	static public function get(int $id): ?User
	{
		return EM::findOneById(User::class, $id);
	}

	static public function getName(int $id): ?string
	{
		$name = DynamicFields::getNameFieldsSQL();
		return EM::getInstance(User::class)->col(sprintf('SELECT %s FROM @TABLE WHERE id = ?;', $name), $id);
	}

	static public function getFromNumber(string $number): ?User
	{
		$field = DynamicFields::getNumberField();
		return EM::findOne(User::class, 'SELECT * FROM @TABLE WHERE ' . $field . ' = ?', $number);
	}

	static public function getNameFromNumber(string $number): ?string
	{
		$name = DynamicFields::getNameFieldsSQL();
		$field = DynamicFields::getNumberField();
		return EM::getInstance(User::class)->col(sprintf('SELECT %s FROM @TABLE WHERE %s = ?;', $name, $field), $number);
	}

	static public function deleteMultiple(array $ids): void
	{
		$session = Session::getInstance();

		if ($session->isLogged()) {
			$user = $session->getUser();

			foreach ($ids as $id) {
				if ($user->id == $id) {
					throw new UserException('Il n\'est pas possible de supprimer son propre compte.');
				}
			}
		}

		foreach ($ids as &$id)
		{
			$id = (int) $id;
			Files::delete(File::CONTEXT_USER . '/' . $id);
		}

		$db = DB::getInstance();

		// Suppression du membre
		$db->delete(User::TABLE, $db->where('id', $membres));
	}

	static public function changeCategory(int $category_id, array $ids)
	{
		$session = Session::getInstance();
		$user_id = null;

		if ($session->isLogged()) {
			$user_id = $session->getUser()->id;
		}

		foreach ($ids as &$id) {
			$id = (int) $id;

			// Don't allow current user ID to change his/her category
			// as that means he/she could be logged out
			if ($id == $user_id) {
				$id = null;
			}
		}

		unset($id);

		// Remove logged-in user ID
		$ids = array_filter($ids);

		$db = DB::getInstance();
		return $db->update(User::TABLE,
			['id_category' => $category_id],
			$db->where('id', $ids)
		);
	}
}