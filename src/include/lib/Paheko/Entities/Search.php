<?php

namespace Paheko\Entities;

use Paheko\AdvancedSearch;
use Paheko\CSV;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Entity;
use Paheko\UserException;
use Paheko\Users\Session;

use Paheko\Accounting\AdvancedSearch as Accounting_AdvancedSearch;
use Paheko\Users\AdvancedSearch as Users_AdvancedSearch;

use KD2\DB\DB_Exception;

class Search extends Entity
{
	const NAME = 'Recherche enregistrée';

	const TABLE = 'searches';

	const TYPE_JSON = 'json';
	const TYPE_SQL = 'sql';
	const TYPE_SQL_UNPROTECTED = 'sql_unprotected';

	const TYPES = [
		self::TYPE_JSON => 'Recherche avancée',
		self::TYPE_SQL => 'Recherche SQL',
		self::TYPE_SQL_UNPROTECTED => 'Recherche SQL non protégée',
	];

	const TARGET_USERS = 'users';
	const TARGET_ACCOUNTING = 'accounting';
	const TARGET_ALL = 'all';

	const TARGETS = [
		self::TARGET_USERS => 'Membres',
		self::TARGET_ACCOUNTING => 'Comptabilité',
	];

	/**
	 * Match the last LIMIT clause from the SQL query
	 * Will match:
	 * SELECT * FROM table LIMIT 5 -> LIMIT 5
	 * SELECT ... (... LIMIT 5) LIMIT 10 -> LIMIT 10
	 * SELECT * FROM (SELECT * FROM bla LIMIT 10) -> no match (as the limit is in a subquery)
	 */
	const LIMIT_REGEXP = '/LIMIT\s+\d+(?:\s*,\s*-?\d+|\s+OFFSET\s+-?\d+)?(?!.*LIMIT\s+-?\d+|.*\))/is';

	protected ?int $id;
	protected ?int $id_user = null;
	protected string $label;
	protected ?string $description = null;
	protected \DateTime $updated;
	protected string $target;
	protected string $type;
	protected string $content;

	protected $_result = null;
	protected $_as = null;
	protected ?DynamicList $_list = null;

	public function selfCheck(): void
	{
		parent::selfCheck();

		$this->assert(strlen($this->label) > 0, 'Le champ libellé doit être renseigné');
		$this->assert(strlen($this->label) <= 500, 'Le champ libellé est trop long');
		$this->assert(is_null($this->description) || strlen($this->description) <= 10000, 'Le champ description est trop long');

		$db = DB::getInstance();

		if ($this->id_user !== null) {
			$this->assert($db->test('users', 'id = ?', $this->id_user), 'Numéro de membre inconnu');
		}

		$this->assert(array_key_exists($this->type, self::TYPES));
		$this->assert(array_key_exists($this->target, self::TARGETS));

		$this->assert(strlen($this->content), 'Le contenu de la recherche ne peut être vide');
		$this->assert(strlen($this->content) <= 100_000, 'Le contenu de la recherche est trop long');

		if ($this->type === self::TYPE_JSON) {
			$this->assert(json_decode($this->content) !== null, 'Recherche invalide pour le type JSON');
		}
	}

	public function getDynamicList(): DynamicList
	{
		if (isset($this->_list)) {
			return $this->_list;
		}

		if ($this->type == self::TYPE_JSON) {
			$this->_list = $this->getAdvancedSearch()->make($this->content);
			$this->_list->setRestrictedTables($this->getProtectedTables());
			return $this->_list;
		}
		else {
			throw new \LogicException('SQL search cannot be used as dynamic list');
		}
	}

	public function getAdvancedSearch(): AdvancedSearch
	{
		if ($this->target == self::TARGET_ACCOUNTING) {
			$class = 'Paheko\Accounting\AdvancedSearch';
		}
		else {
			$class = 'Paheko\Users\AdvancedSearch';
		}

		if (null === $this->_as || !is_a($this->_as, $class)) {
			$this->_as = new $class;
		}

		return $this->_as;
	}

	public function transformToSQL()
	{
		if ($this->type != self::TYPE_JSON) {
			throw new \LogicException('Cannot transform a non-JSON search to SQL');
		}

		$sql = $this->getDynamicList()->SQL();

		// Remove indentation
		$sql = preg_replace('/^\s*/m', '', $sql);

		$this->set('content', $sql);
		$this->set('type', self::TYPE_SQL);
	}

	public function SQL(array $options = []): string
	{
		if ($this->type == self::TYPE_JSON) {
			$sql = $this->getDynamicList()->SQL();
		}
		else {
			$sql = $this->content;
		}

		$has_limit = stripos($sql, 'LIMIT') !== false;

		// force LIMIT
		if (!empty($options['limit'])) {
			$regexp = $has_limit ? self::LIMIT_REGEXP : '/;[^;]*$|(<?=;)$/s';
			$limit = ' LIMIT ' . (int) $options['limit'];
			$sql = preg_replace($regexp, $limit, trim($sql));
		}
		elseif (!empty($options['no_limit']) && $has_limit) {
			$sql = preg_replace(self::LIMIT_REGEXP, '', $sql);
		}

		if (!empty($options['select_also'])) {
			$sql = preg_replace('/^\s*SELECT\s+(.*?)\s+FROM\s+/Uis', 'SELECT $1, ' . implode(', ', (array)$options['select_also']) . ' FROM ', $sql);
		}
		elseif (!empty($options['select'])) {
			$sql = preg_replace('/^\s*SELECT\s+(.*?)\s+FROM\s+/Uis', 'SELECT ' . implode(', ', (array)$options['select']) . ' FROM ', $sql);
		}

		$sql = trim($sql, "\n\r\t; ");

		return $sql;
	}

	/**
	 * Returns a SQLite3Result for the current search
	 */
	public function query(array $options = []): \SQLite3Result
	{
		if (null !== $this->_result && empty($options['no_cache'])) {
			return $this->_result;
		}

		$sql = $this->SQL($options);

		$allowed_tables = $this->getProtectedTables();
		$db = DB::getInstance()->getRestrictedConnection(['rules' => $allowed_tables, 'unicode_like' => true]);

		try {
			$st = $db->prepare($sql);
			$result = $db->execute($st);

			if (empty($options['no_cache'])) {
				$this->_result = $result;
			}

			return $result;
		}
		catch (DB_Exception $e) {
			throw new UserException('Erreur dans la requête : ' . $e->getMessage(), 0, $e);
		}
	}

	public function getHeader(array $options = []): array
	{
		$r = $this->query($options);
		$columns = [];

		for ($i = 0; $i < $r->numColumns(); $i++) {
			$columns[] = $r->columnName($i);
		}

		return $columns;
	}

	public function iterateResults(): iterable
	{
		$r = $this->query();

		while ($row = $r->fetchArray(\SQLITE3_NUM)) {
			yield $row;
		}
	}

	public function hasUserId(): bool
	{
		try {
			$sql = $this->SQL();

			if (!preg_match('/(?:FROM|JOIN)\s+users/i', $sql)) {
				return false;
			}

			$header = $this->getHeader(['limit' => 1, 'no_cache' => true]);
		}
		catch (UserException $e) {
			return false;
		}

		if (!in_array('id', $header) && !in_array('_user_id', $header)) {
			return false;
		}

		return true;
	}

	public function hasLimit(): bool
	{
		if ($this->type === self::TYPE_JSON) {
			return false;
		}

		return (bool) preg_match(self::LIMIT_REGEXP, $this->SQL());
	}

	public function countResults(bool $ignore_errors = true): ?int
	{
		$sql = $this->SQL(['no_limit' => true, 'no_cache' => true]);
		$sql = rtrim($sql);
		$sql = rtrim($sql, ';');
		$sql = 'SELECT COUNT(*) FROM (' . $sql . ')';

		$allowed_tables = $this->getProtectedTables();
		$db = DB::getInstance()->getRestrictedConnection(['rules' => $allowed_tables, 'unicode_like' => true]);

		try {
			$st = $db->prepare($sql);
			$r = $db->execute($st);

			$count = (int) $r->fetchArray(\SQLITE3_NUM)[0] ?? 0;
			$r->finalize();
			$st->close();
			return $count;
		}
		catch (DB_Exception $e) {
			if ($ignore_errors) {
				return null;
			}

			throw new UserException('Erreur dans la requête : ' . $e->getMessage(), 0, $e);
		}
	}

	public function export(string $format, string $title = 'Recherche')
	{
		CSV::export($format, $title, $this->iterateResults(), $this->getHeader());
	}

	public function schema(): array
	{
		$out = [];
		$db = DB::getInstance();

		foreach ($this->getAdvancedSearch()->schemaTables() as $table => $comment) {
			$schema = $db->getTableSchema($table);
			$schema['comment'] = $comment;
			$out[$table] = $schema;
		}

		return $out;
	}

	public function getProtectedTables(): ?array
	{
		if ($this->target === self::TARGET_ALL
			|| $this->type === self::TYPE_SQL_UNPROTECTED) {
			return DB::DEFAULT_AUTHORIZER_RULES;
		}

		$list = $this->getAdvancedSearch()->tables();
		$tables = [];

		foreach ($list as $name) {
			$tables[$name] = null;

			if ($name === 'users') {
				// Make sure we block access to private fields of users table
				$tables[$name] = DB::DEFAULT_AUTHORIZER_RULES['users'];
			}
		}

		return $tables;
	}

	public function getGroups(): array
	{
		if ($this->type !== self::TYPE_JSON) {
			throw new \LogicException('Only JSON searches can use this method');
		}

		return json_decode($this->content, true)['groups'];
	}

	public function simple(string $query, array $options = []): self
	{
		$this->_list = null;
		$this->set('content', json_encode($this->getAdvancedSearch()->simple($query, $options)));
		$this->set('type', self::TYPE_JSON);
		return $this;
	}

	public function redirect(string $query, array $options = []): bool
	{
		return $this->getAdvancedSearch()->redirect($query, $options);
	}

	public function redirectIfSingleResult(): bool
	{
		if ($this->getDynamicList()->count() !== 1) {
			return false;
		}

		$this->getAdvancedSearch()->redirectResult($this->getDynamicList()->iterate()->current());
		return true;
	}

	public function importForm(?array $source = null)
	{
		$source ??= $_POST;

		if (!empty($source['public'])) {
			$source['id_user'] = null;
		}
		elseif (isset($source['public'])) {
			$source['id_user'] = Session::getUserId();
		}

		parent::importForm($source);
	}

	/**
	 * Populate object from what was passed in query string / form (POST)
	 * Return TRUE if no data was passed and the default was loaded instead
	 * (in that case, the results shouldn't be loaded/displayed, only the search form).
	 */
	public function populate(Session $session): ?bool
	{
		$as = $this->getAdvancedSearch();
		$as->setSession($session);
		$as->requireAccess();

		$text_query = trim($_GET['qt'] ?? '');
		$sql_query = trim($_POST['sql'] ?? '');
		$json_query = isset($_POST['q']) ? json_decode($_POST['q'], true) : null;
		$default = false;

		if ($sql_query !== '') {
			// Only admins can write custom SQL queries
			if (!$as->isSQLAllowed()) {
				throw new UserException('Vous n\'avez pas le droit d\'exécuter cette recherche');
			}

			if (!empty($_POST['unprotected'])) {
				if (!$as->isUnprotectedSQLAllowed()) {
					throw new UserException('Vous n\'avez pas le droit d\'exécuter cette recherche');
				}

				$this->type = self::TYPE_SQL_UNPROTECTED;
			}
			else {
				$this->type = self::TYPE_SQL;
			}

			$this->content = $sql_query;
		}
		elseif ($json_query !== null) {
			if ($as->hasRestrictedColumns($json_query)) {
				throw new \LogicException('Invalid groups query: trying to access a restricted column');
			}

			$this->content = json_encode(['groups' => $json_query]);
			$this->type = self::TYPE_JSON;
		}
		elseif ($text_query !== '') {
			$options = [
				'id_year'     => $_GET['year'] ?? null,
				'id_category' => $_GET['id_category'] ?? null,
			];

			if ($this->redirect($text_query, $options)) {
				return null;
			}

			$this->simple($text_query, $options);

			if ($this->redirectIfSingleResult()) {
				return null;
			}
		}
		elseif (!isset($this->content)) {
			$this->content = json_encode($this->getAdvancedSearch()->defaults());
			$this->type = self::TYPE_JSON;
			$default = true;
		}

		if (!empty($_POST['to_sql']) && $as->isSQLAllowed()) {
			$this->transformToSQL();
		}

		return $default;
	}

	public function save(bool $selfcheck = true): bool
	{
		if ($this->isModified()) {
			$this->set('updated', new \DateTime);
		}

		return parent::save($selfcheck);
	}

	/**
	 * Return TRUE if user shouldn't be able to change the search (eg. groups for JSON or query for SQL)
	 */
	public function isLocked(): bool
	{
		$as = $this->getAdvancedSearch();

		// If SQL query and user cannot do write SQL queries, lock the search
		if (in_array($this->type, [self::TYPE_SQL, self::TYPE_SQL_UNPROTECTED], true)
			&& !$as->isSQLAllowed()) {
			return true;
		}

		// If user cannot write unprotected SQL queries
		if ($this->type === self::TYPE_SQL_UNPROTECTED
			&& !$as->isUnprotectedSQLAllowed()) {
			return true;
		}

		// eg. for user search, search can be locked if it contains columns
		// the user doesn't have access to
		if ($this->type === self::TYPE_JSON) {
			return $as->hasRestrictedColumns($this->getGroups());
		}

		return false;
	}
}
