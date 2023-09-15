<?php
declare(strict_types=1);

namespace Paheko\Users;

use Paheko\Entities\Users\Category;
use Paheko\Entities\Users\User;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;

use Paheko\Config;
use Paheko\CSV;
use Paheko\CSV_Custom;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Search;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\ValidationException;

use KD2\SMTP;
use KD2\DB\EntityManager as EM;

class Users
{
	static public function create(): User
	{
		$default_category = Config::getInstance()->default_category;
		$user = new User;
		$user->set('id_category', $default_category);
		return $user;
	}

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

	static protected function iterateEmails(array $sql, string $email_column = '_email'): \Generator
	{
		foreach (DB::getInstance()->iterate(implode(' UNION ALL ', $sql)) as $row) {
			yield $row->$email_column => $row;
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
			$sql[] = sprintf('SELECT *, %s AS _email, NULL AS preferences FROM users WHERE %s AND %1$s IS NOT NULL', $db->quoteIdentifier($field), $where);
		}

		return self::iterateEmails($sql);
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
			$sql[] = sprintf('SELECT u.*, u.%s AS _email, NULL AS preferences FROM users u INNER JOIN users_active_services s ON s.id = u.id
				WHERE s.service = %d AND %1$s IS NOT NULL', $db->quoteIdentifier($field), $id_service);
		}

		return self::iterateEmails($sql);
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
			$sql[] = sprintf('SELECT u.*, u.%s AS _email, NULL AS preferences FROM users u INNER JOIN users_tmp_search AS s ON s.id = u.id', $db->quoteIdentifier($field));
		}

		return self::iterateEmails($sql);
	}

	static public function listByCategory(?int $id_category = null): DynamicList
	{
		$db = DB::getInstance();
		$df = DynamicFields::getInstance();
		$number_field = $df->getNumberField();
		$name_fields = $df->getNameFields();

		$columns = [
			'_user_id' => [
				'select' => 'u.id',
			],
			'_user_name_index' => [
				'select' => $df::getNameFieldsSearchableSQL('s'),
			],
		];

		$number_column = [
			'label' => 'Num.',
			'select' => 'u.' . $number_field,
		];

		$identity_column = [
			'label' => $df->getNameLabel(),
			'select' => $df->getNameFieldsSQL('u'),
			'order' => '_user_name_index %s',
		];

		$fields = $df->getListedFields();

		foreach ($fields as $key => $config) {
			// Skip number field
			if ($key === $number_field) {
				if (null !== $number_column) {
					$columns['number'] = $number_column;
					$number_column = null;
				}

				continue;
			}
			// Skip name fields
			elseif (in_array($key, $name_fields)) {
				if (null !== $identity_column) {
					$columns['identity'] = $identity_column;
					$identity_column = null;
				}

				continue;
			}

			$columns[$key] = [
				'label'  => $config->label,
				'select' => 'u.' . $key,
			];

			if ($config->hasSearchCache($key)) {
				$columns[$key]['order'] = sprintf('s.%s %%s', $key);
			}

			if ($config->type == 'file') {
				$columns[$key]['select'] = sprintf('(SELECT GROUP_CONCAT(f.path, \';\')
					FROM users_files uf
					INNER JOIN files f ON f.id = uf.id_file AND f.trash IS NULL
					WHERE uf.id_user = u.id AND uf.field = %s)',
					$db->quote($key)
				);
			}
		}

		if (null !== $identity_column) {
			$columns['identity'] = $identity_column;
		}

		$tables = 'users u';
		$tables .= ' INNER JOIN users_search s ON s.id = u.id';

		if ($db->test('users', 'is_parent = 1')) {
			$tables .= ' LEFT JOIN users b ON b.id = u.id_parent';

			$columns['id_parent'] = [
				'label'  => 'Rattaché à',
				'select' => 'u.id_parent',
				'order'  => 'u.id_parent IS NULL, _parent_name COLLATE U_NOCASE %s, _user_name_index %1$s',
			];

			$columns['_parent_name'] = [
				'select' => sprintf('CASE WHEN u.id_parent IS NOT NULL THEN %s ELSE NULL END', $df->getNameFieldsSQL('b')),
			];

			$columns['is_parent'] = [
				'label' => 'Responsable',
				'select' => 'u.is_parent',
				'order' => 'u.is_parent DESC, _user_name_index %1$s',
			];
		}

		if (!$id_category) {
			$conditions = sprintf('u.id_category IN (SELECT id FROM users_categories WHERE hidden = 0)');
		}
		elseif ($id_category > 0) {
			$conditions = sprintf('u.id_category = %d', $id_category);
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
		return EM::getInstance(User::class)->col(sprintf('SELECT %s FROM @TABLE WHERE id = ?;', $name), $id) ?: null;
	}

	static public function getNames(array $ids): array
	{
		$name = DynamicFields::getNameFieldsSQL();
		$db = EM::getInstance(User::class)->DB();
		return $db->getAssoc(sprintf('SELECT id, %s FROM users WHERE %s;', $name, $db->where('id', $ids)));
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
		return EM::getInstance(User::class)->col(sprintf('SELECT %s FROM @TABLE WHERE %s = ?;', $name, $field), $number) ?: null;
	}

	static public function deleteSelected(array $ids): void
	{
		$ids = array_map('intval', $ids);

		if ($logged_user_id = Session::getUserId()) {
			if (in_array($logged_user_id, $ids)) {
				throw new UserException('Il n\'est pas possible de supprimer son propre compte.');
			}
		}

		foreach ($ids as $id) {
			Files::delete(File::CONTEXT_USER . '/' . $id);
		}

		$db = DB::getInstance();

		// Suppression du membre
		$db->delete(User::TABLE, $db->where('id', $ids));
	}

	static public function deleteFilesSelected(array $ids): void
	{
		$ids = array_map('intval', $ids);

		foreach ($ids as $id) {
			Files::delete(File::CONTEXT_USER . '/' . $id);
		}
	}

	static public function changeCategorySelected(int $category_id, array $ids): void
	{
		$db = DB::getInstance();

		if (!$db->test(Category::TABLE, 'id = ?', $category_id)) {
			throw new \InvalidArgumentException('Invalid category ID: ' . $category_id);
		}

		$ids = array_map('intval', $ids);

		// Don't allow current user ID to change his/her category
		$logged_user_id = Session::getUserId();
		$ids = array_filter($ids, fn($a) => $a != $logged_user_id);

		$db->update(User::TABLE,
			['id_category' => $category_id],
			$db->where('id', $ids)
		);
	}

	static public function exportSelected(string $format, array $ids): void
	{
		$db = DB::getInstance();

		$ids = array_map('intval', $ids);
		$where = $db->where('id', $ids);
		$name = sprintf('Liste de %d membres', count($ids));
		self::exportWhere($format, $name, $where);
	}

	static public function exportCategory(string $format, int $id_category): void
	{
		if ($id_category == -1) {
			$name = 'Tous les membres';
			$where = '1';
		}
		elseif (!$id_category) {
			$name = 'Membres sauf catégories cachées';
			$where = 'id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)';
		}
		else {
			$cat = Categories::get($id_category);
			$name = sprintf('Membres - %s', $cat->name);
			$where = sprintf('id_category = %d', $id_category);
		}

		self::exportWhere($format, $name, $where);
	}

	static public function export(string $format): void
	{
		self::exportWhere($format, 'Tous les membres', '1');
	}

	static protected function exportWhere(string $format, string $name, string $where): void
	{
		$df = DynamicFields::getInstance();
		$db = DB::getInstance();

		$header = $df->listAssocNames();
		$columns = array_keys($header);
		$columns = array_map([$db, 'quoteIdentifier'], $columns);
		$columns = implode(', ', $columns);
		$header['category'] = 'Catégorie';

		$i = $db->iterate(sprintf('SELECT %s, (SELECT name FROM users_categories WHERE id = users.id_category) AS category FROM users WHERE %s;', $columns, $where));

		$callback = function (&$row) use ($df) {
			foreach ($df->fieldsByType('date') as $f) {
				if (isset($row->{$f->name})) {
					$row->{$f->name} = \DateTime::createFromFormat('!Y-m-d', $row->{$f->name});
				}
			}
			foreach ($df->fieldsByType('datetime') as $f) {
				if (isset($row->{$f->name})) {
					$row->{$f->name} = \DateTime::createFromFormat('!Y-m-d H:i:s', $row->{$f->name});
				}
			}
		};

		CSV::export($format, $name, $i, $header, $callback);
	}

	static public function importReport(CSV_Custom $csv, string $mode, ?int $logged_user_id = null): array
	{
		$report = ['created' => [], 'modified' => [], 'unchanged' => [], 'errors' => []];

		if ($logged_user_id) {
			$report['has_logged_user'] = false;
		}

		foreach (self::iterateImport($csv, $mode, $report['errors']) as $line => $user) {
			if ($logged_user_id && $user->id == $logged_user_id) {
				$report['has_logged_user'] = true;
				continue;
			}

			try {
				$user->selfCheck();
			}
			catch (UserException $e) {
				$report['errors'][] = sprintf('Ligne %d (%s) : %s', $line, $user->name(), $e->getMessage());
				continue;
			}

			if (!$user->exists()) {
				$report['created'][] = $user;
			}
			elseif ($user->isModified()) {
				$report['modified'][] = $user;
			}
			else {
				$report['unchanged'][] = $user;
			}
		}

		return $report;
	}

	static public function import(CSV_Custom $csv, string $mode, ?int $logged_user_id = null): void
	{
		$db = DB::getInstance();
		$db->begin();

		foreach (self::iterateImport($csv, $mode) as $i => $user) {
			// Skip logged user, to avoid changing own login field
			if ($logged_user_id && $user->id == $logged_user_id) {
				continue;
			}

			try {
				$user->save();
			}
			catch (UserException $e) {
				throw new UserException(sprintf('Ligne %d : %s', $i, $e->getMessage()), 0, $e);
			}
		}

		$db->commit();
	}

	static public function iterateImport(CSV_Custom $csv, string $mode, ?array &$errors = null): \Generator
	{
		$number_field = DynamicFields::getNumberField();

		foreach ($csv->iterate() as $i => $row) {
			$user = null;

			try {
				if ($mode === 'update') {
					if (empty($row->$number_field)) {
						throw new UserException('Aucun numéro de membre n\'a été indiqué');
					}

					$user = self::getFromNumber($row->$number_field);

					if (!$user) {
						$msg = sprintf('Le membre avec le numéro "%s" n\'existe pas.', $row->$number_field);
						throw new UserException($msg);
					}
				}
				elseif ($mode === 'auto' && !empty($row->$number_field)) {
					$user = self::getFromNumber($row->$number_field);
				}

				if (!$user) {
					$user = self::create();

					if ($mode === 'create' || empty($row->$number_field)) {
						$user->$number_field = null;
						$user->setNumberIfEmpty();
						unset($row->$number_field);
					}
				}

				$user->importForm((array)$row);
				yield $i => $user;
			}
			catch (UserException $e) {
				if (null !== $errors) {
					$errors[] = sprintf('Ligne %d : %s', $i, $e->getMessage());
					continue;
				}

				throw $e;
			}
		}
	}
}
