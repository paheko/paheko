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
use Paheko\Log;
use Paheko\Search;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\ValidationException;

use KD2\SMTP;
use KD2\DB\EntityManager as EM;

use KD2\Graphics\SVG\Avatar;

use const Paheko\{ADMIN_COLOR1, ADMIN_COLOR2, LOCAL_LOGIN, DESKTOP_CONFIG_FILE};

class Users
{
	const IMPORT_MODE_AUTO = 'auto';
	const IMPORT_MODE_CREATE = 'create';
	const IMPORT_MODE_UPDATE= 'update';

	const IMPORT_MODES = [
		self::IMPORT_MODE_AUTO,
		self::IMPORT_MODE_CREATE,
		self::IMPORT_MODE_UPDATE,
	];

	static public function create(): User
	{
		$default_category = Config::getInstance()->default_category;
		$user = new User;
		$user->set('id_category', $default_category);
		return $user;
	}

	static public function hasParents(): bool
	{
		return DB::getInstance()->test('users', 'is_parent = 1');
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

		$columns = array_map([$db, 'quoteIdentifier'], $header);
		$columns = implode(', ', $columns);

		// We only need the user id, store it in a temporary table for now
		$db->exec(sprintf('DROP TABLE IF EXISTS users_tmp_search; CREATE TEMPORARY TABLE IF NOT EXISTS users_tmp_search (%s);', $columns));
		$db->exec(sprintf('INSERT INTO users_tmp_search SELECT * FROM (%s)', $s->SQL(['no_limit' => true])));

		$fields = DynamicFields::getEmailFields();

		$sql = [];

		foreach ($fields as $field) {
			$sql[] = sprintf('SELECT s.*, u.*, u.%s AS _email, NULL AS preferences
				FROM users u
				INNER JOIN users_tmp_search AS s ON s.%s = u.id
				WHERE u.%1$s IS NOT NULL',
				$db->quoteIdentifier($field),
				$db->quoteIdentifier($id_column)
			);
		}

		return self::iterateEmails($sql);
	}

	static public function listByCategory(?int $id_category = null, ?Session $session = null): DynamicList
	{
		$db = DB::getInstance();
		$df = DynamicFields::getInstance();
		$number_field = $df->getNumberField();
		$name_fields = $df->getNameFields();
		$number_field_sql = 'u.' . $db->quoteIdentifier($number_field);

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
			'select' => 'u.' . $db->quoteIdentifier($number_field),
		];

		$identity_column = [
			'label' => $df->getNameLabel(),
			'select' => $df->getNameFieldsSQL('u'),
			'order' => '_user_name_index %s, ' . $number_field_sql . ' %1$s',
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

			if ($session && !$session->canAccess(Session::SECTION_USERS, $config->management_access_level)) {
				continue;
			}

			$columns[$key] = [
				'label'  => $config->label,
				'select' => 'u.' . $db->quoteIdentifier($key),
				'order'  => 'u.' . $db->quoteIdentifier($key),
			];

			if ($config->hasSearchCache($key)) {
				$columns[$key]['order'] = 's.' . $db->quoteIdentifier($key);
			}

			$columns[$key]['order'] = sprintf(
				'%s IS NULL %%s, %1$s %%1$s, %s %%1$s',
				$columns[$key]['order'],
				$number_field_sql
			);

			if ($config->type == 'file') {
				$columns[$key]['select'] = sprintf('(SELECT json_group_array(f.path)
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

		$tables = 'users_view u';
		$tables .= ' LEFT JOIN users_search s ON s.id = u.id';

		if (self::hasParents()) {
			$tables .= ' LEFT JOIN users b ON b.id = u.id_parent';
			$config = Config::getInstance();

			if ($config->show_parent_column !== false) {
				$columns['id_parent'] = [
					'label'  => 'Rattaché à',
					'select' => 'u.id_parent',
					'order'  => 'u.id_parent IS NULL, _parent_name COLLATE U_NOCASE %s, _user_name_index %1$s, ' . $number_field_sql . ' %1$s',
				];

				$columns['_parent_name'] = [
					'select' => sprintf('CASE WHEN u.id_parent IS NOT NULL THEN %s ELSE NULL END', $df->getNameFieldsSQL('b')),
				];
			}

			if ($config->show_has_children_column !== false) {
				$columns['is_parent'] = [
					'label' => 'Responsable',
					'select' => 'u.is_parent',
					'order' => 'u.is_parent DESC, _user_name_index %1$s, ' . $number_field_sql . ' %1$s',
				];
			}
		}

		if (!$id_category) {
			$conditions = 'u.id_category IN (SELECT id FROM users_categories WHERE hidden = 0)';
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
		return EM::findOneById(User::class, $id, 'users_view');
	}

	static public function getName(int $id): ?string
	{
		$name = DynamicFields::getNameFieldsSQL();
		$found = EM::getInstance(User::class)->col(sprintf('SELECT %s FROM @TABLE WHERE id = ?;', $name), $id);
		$found = (string) $found;
		return $found ?: null;
	}

	static public function getNames(array $ids): array
	{
		$name = DynamicFields::getNameFieldsSQL();
		$db = EM::getInstance(User::class)->DB();
		return $db->getAssoc(sprintf('SELECT id, %s FROM users WHERE %s;', $name, $db->where('id', $ids)));
	}

	static public function getFromNumber(string $number): ?User
	{
		$field = DynamicFields::getNumberFieldSQL();
		return EM::findOne(User::class, 'SELECT * FROM @TABLE_view WHERE ' . $field . ' = ?', $number);
	}

	static public function getIdFromNumber(string $number): ?int
	{
		$field = DynamicFields::getNumberFieldSQL();
		return EM::getInstance(User::class)->col('SELECT id FROM @TABLE WHERE ' . $field . ' = ?', $number) ?: null;
	}

	static public function getNameFromNumber(string $number): ?string
	{
		$name = DynamicFields::getNameFieldsSQL();
		$field = DynamicFields::getNumberFieldSQL();
		$found = EM::getInstance(User::class)->col(sprintf('SELECT %s FROM @TABLE WHERE %s = ?;', $name, $field), $number);
		$found = (string) $found;
		return $found ?: null;
	}

	static public function getFromLogin(string $login): ?User
	{
		$db = DB::getInstance();
		$field = $db->quoteIdentifier(DynamicFields::getLoginField());

		if ($field === 'id') {
			$login = (int) $login;
		}
		else {
			$login = trim($login);
		}

		return EM::findOne(User::class, 'SELECT * FROM @TABLE_view WHERE ' . $field . ' = ? COLLATE U_NOCASE LIMIT 1;', $login);
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

	static public function changeCategorySelected(int $id_category, array $ids, Session $session): void
	{
		$db = DB::getInstance();

		$safe_categories = Categories::listAssocSafe($session);

		if (!array_key_exists($id_category, $safe_categories)) {
			throw new UserException('Vous n\'avez pas le droit de placer ce membre dans cette catégorie');
		}

		$ids = array_map('intval', $ids);

		// Don't allow current user ID to change his/her category
		$logged_user_id = $session->user()->id();
		$ids = array_filter($ids, fn($a) => $a != $logged_user_id);

		$db->update(User::TABLE,
			['id_category' => $id_category, 'date_updated' => new \DateTime],
			$db->where('id', $ids)
		);
	}

	static public function exportSelected(string $format, array $ids): void
	{
		$db = DB::getInstance();

		$ids = array_map('intval', $ids);
		$where = 'u.' . $db->where('id', $ids);
		$name = sprintf('Liste de %d membres', count($ids));
		self::exportWhere($format, $name, $where);
	}

	static public function exportCategory(string $format, int $id_category, bool $with_id = false): void
	{
		if ($id_category == -1) {
			$name = 'Tous les membres';
			$where = '1';
		}
		elseif (!$id_category) {
			$name = 'Membres sauf catégories cachées';
			$where = 'u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)';
		}
		else {
			$cat = Categories::get($id_category);

			if (!$cat) {
				throw new \InvalidArgumentException('This category does not exist');
			}

			$name = sprintf('Membres - %s', $cat->name);
			$where = sprintf('u.id_category = %d', $id_category);
		}

		self::exportWhere($format, $name, $where, $with_id);
	}

	static public function export(string $format, bool $with_id = false): void
	{
		self::exportWhere($format, 'Tous les membres', '1', $with_id);
	}

	static protected function exportWhere(string $format, string $name, string $where, bool $with_id = false): void
	{
		$df = DynamicFields::getInstance();
		$db = DB::getInstance();

		$tables = 'users_view u';
		$header = $df->listAssocNames();

		if ($with_id) {
			$header = array_merge(['id' => 'id'], $header);
		}

		$columns = array_keys($header);
		$columns = array_map([$db, 'quoteIdentifier'], $columns);

		if (self::hasParents()) {
			$columns = array_map(fn($a) => 'u.' . $a, $columns);
			$tables .= ' LEFT JOIN users b ON b.id = u.id_parent';
			$tables .= ' LEFT JOIN users c ON c.id_parent = u.id';

			$columns[] = sprintf('CASE WHEN u.id_parent IS NOT NULL THEN %s ELSE NULL END AS parent_name', $df->getNameFieldsSQL('b'));
			$columns[] = sprintf('CASE WHEN u.is_parent THEN GROUP_CONCAT(%s, \'%s\') ELSE NULL END AS children_names', $df->getNameFieldsSQL('c'), "\n");

			$header['parent_name'] = 'Rattaché à';
			$header['children_names'] = 'Membres rattachés';
		}

		$columns = implode(', ', $columns);
		$header['category'] = 'Catégorie';

		$i = $db->iterate(sprintf('SELECT %s, (SELECT name FROM users_categories WHERE id = u.id_category) AS category
			FROM %s WHERE %s GROUP BY u.id ORDER BY %s;', $columns, $tables, $where, $df->getNameFieldsSQL('u')));

		CSV::export($format, $name, $i, $header, [self::class, 'exportRowCallback']);
	}

	static public function exportRowCallback(&$row) {
		$df = DynamicFields::getInstance();

		foreach ($row as $key => &$value) {
			$field = $df->get($key);

			if (!$field || null === $value) {
				continue;
			}

			if ($field->type === 'date' && is_string($value)) {
				$value = \DateTime::createFromFormat('!Y-m-d', $value);
			}
			elseif ($field->type === 'datetime' && is_string($value)) {
				$value = \DateTime::createFromFormat('!Y-m-d', $value);
			}
			else {
				$value = $field->getStringValue($value);
			}
		}

		unset($value);
	}

	static public function importReport(CSV_Custom $csv, string $mode, ?Session $session = null): array
	{
		if (!in_array($mode, self::IMPORT_MODES)) {
			throw new \InvalidArgumentException('Invalid import mode: ' . $mode);
		}

		$report = ['created' => [], 'modified' => [], 'unchanged' => [], 'errors' => []];

		$logged_user_id = $session ? $session::getUserId() : null;
		$is_logged = $session ? $session->isLogged() : null;

		if ($logged_user_id) {
			$report['has_logged_user'] = false;
		}

		if ($is_logged) {
			$report['has_admin_users'] = true;
		}

		foreach (self::iterateImport($csv, $mode, $report['errors']) as $line => $user) {
			if ($logged_user_id && $user->id == $logged_user_id) {
				$report['has_logged_user'] = true;
				continue;
			}
			elseif ($is_logged && !$user->canBeModifiedBy($session)) {
				$report['has_admin_users'] = true;
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

	static public function import(CSV_Custom $csv, string $mode, ?Session $session = null): void
	{
		if (!in_array($mode, self::IMPORT_MODES)) {
			throw new \InvalidArgumentException('Invalid import mode: ' . $mode);
		}

		$logged_user_id = $session ? $session::getUserId() : null;
		$is_logged = $session ? $session->isLogged() : null;

		$number_field = DynamicFields::getNumberField();
		$db = DB::getInstance();
		$db->begin();

		Log::add(Log::MESSAGE, ['message' => 'Import de membres'], $session ? $session->user()->id : null);

		foreach (self::iterateImport($csv, $mode) as $i => $user) {
			// Skip logged user, to avoid changing own login field
			if ($logged_user_id && $user->id == $logged_user_id) {
				continue;
			}
			elseif ($is_logged && !$user->canBeModifiedBy($session)) {
				continue;
			}

			try {
				if (empty($user->$number_field)) {
					$user->$number_field = null;
				}

				if ($mode === 'create' || empty($user->$number_field)) {
					$user->setNumberIfEmpty();
				}

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
		if (!in_array($mode, self::IMPORT_MODES)) {
			throw new \InvalidArgumentException('Invalid import mode: ' . $mode);
		}

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

	static public function serveAvatar($id): void
	{
		$config = Config::getInstance();
		$name = (string)($id ?: Utils::getIp());

		if (ctype_digit($name)) {
			$colors = [$config->color1 ?: ADMIN_COLOR1, $config->color2 ?: ADMIN_COLOR2];

			// Add more random colors
			foreach ($colors as $color) {
				$rgb = Utils::rgbHexToDec($color);
				$rgb[0] += 25;
				$rgb[1] -= 25;
				$rgb[2] += 25;
				$colors[] = Utils::rgbDecToHex($rgb);
				$rgb[0] -= 50;
				$rgb[1] += 50;
				$rgb[2] -= 50;
				$colors[] = Utils::rgbDecToHex($rgb);
			}
		}
		else {
			$colors = ['#999999', '#cccccc', '#666666'];
		}

		header('Content-Type: image/svg+xml; charset=utf-8');
		echo Avatar::beam($name, ['colors' => $colors, 'size' => 128, 'square' => true]);
	}

	static public function canConfigureDesktopLogin(): bool
	{
		if (!DESKTOP_CONFIG_FILE) {
			return false;
		}

		return (bool) DB::getInstance()->firstColumn('SELECT 1 FROM users WHERE password IS NOT NULL
			AND id_category IN (SELECT id FROM users_categories WHERE perm_config >= ? AND perm_connect >= ?) LIMIT 1;',
			Session::ACCESS_ADMIN, Session::ACCESS_READ);
	}

	static public function getFirstAdmin(): ?User
	{
		return EM::findOne(User::class, 'SELECT * FROM @TABLE WHERE id_category IN (SELECT id FROM users_categories WHERE perm_config >= ?) LIMIT 1;',
			Session::ACCESS_ADMIN);
	}

	static public function getNewNumber(): ?int
	{
		$field = DynamicFields::getNumberFieldSQL();
		$db = DB::getInstance();
		$r = $db->firstColumn(sprintf('SELECT MAX(%s) FROM %s;', $field, User::TABLE));

		if (!is_int($r) && !ctype_digit($r)) {
			return null;
		}

		return intval($r) + 1;
	}
}
