<?php

namespace Paheko\Entities;

use Paheko\AdvancedSearch;
use Paheko\CSV;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Entity;
use Paheko\UserException;

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
	protected \DateTime $created;
	protected string $target;
	protected string $type;
	protected string $content;

	protected $_result = null;
	protected $_as = null;

	public function selfCheck(): void
	{
		parent::selfCheck();

		$this->assert(strlen('label') > 0, 'Le champ libellé doit être renseigné');
		$this->assert(strlen('label') <= 500, 'Le champ libellé est trop long');

		$db = DB::getInstance();

		if ($this->id_user !== null) {
			$this->assert($db->test('users', 'id = ?', $this->id_user), 'Numéro de membre inconnu');
		}

		$this->assert(array_key_exists($this->type, self::TYPES));
		$this->assert(array_key_exists($this->target, self::TARGETS));

		$this->assert(strlen($this->content), 'Le contenu de la recherche ne peut être vide');

		if ($this->type === self::TYPE_JSON) {
			$this->assert(json_decode($this->content) !== null, 'Recherche invalide pour le type JSON');
		}
	}

	public function getDynamicList(): DynamicList
	{
		if ($this->type == self::TYPE_JSON) {
			return $this->getAdvancedSearch()->make($this->content);
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
			$regexp = $has_limit ? self::LIMIT_REGEXP : '/;.*$|$/s';
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
		$db = DB::getInstance();

		try {
			$db->toggleUnicodeLike(true);

			// Lock database against changes
			$db->setReadOnly(true);

			$st = $db->protectSelect($allowed_tables, $sql);
			$result = $db->execute($st);

			$db->setReadOnly(false);

			if (empty($options['no_cache'])) {
				$this->_result = $result;
			}

			return $result;
		}
		catch (DB_Exception $e) {
			throw new UserException('Erreur dans la requête : ' . $e->getMessage(), 0, $e);
		}
		finally {
			$db->toggleUnicodeLike(false);
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
		$sql = $this->SQL();

		if (!preg_match('/(?:FROM|JOIN)\s+users/i', $sql)) {
			return false;
		}

		try {
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

	public function countResults(): int
	{
		$sql = $this->SQL(['no_limit' => true]);
		$sql = 'SELECT COUNT(*) FROM (' . $sql . ')';

		$allowed_tables = $this->getProtectedTables();
		$db = DB::getInstance();

		try {
			$db->toggleUnicodeLike(true);

			// Lock database against changes
			$db->setReadOnly(true);
			$st = $db->protectSelect($allowed_tables, $sql);
			$r = $db->execute($st);
			$db->setReadOnly(false);

			$count = (int) $r->fetchArray(\SQLITE3_NUM)[0] ?? 0;
			$r->finalize();
			$st->close();
			return $count;
		}
		catch (DB_Exception $e) {
			throw new UserException('Erreur dans la requête : ' . $e->getMessage(), 0, $e);
		}
		finally {
			$db->toggleUnicodeLike(false);
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
		if ($this->type != self::TYPE_SQL || $this->target == self::TARGET_ALL) {
			return null;
		}

		$list = $this->getAdvancedSearch()->tables();
		$tables = [];

		foreach ($list as $name) {
			$tables[$name] = null;
		}

		return $tables;
	}

	public function getGroups(): array
	{
		if ($this->type != self::TYPE_JSON) {
			throw new \LogicException('Only JSON searches can use this method');
		}

		return json_decode($this->content, true)['groups'];
	}

	public function quick(string $query): DynamicList
	{
		$this->content = json_encode($this->getAdvancedSearch()->simple($query, false));
		$this->type = self::TYPE_JSON;
		return $this->getDynamicList();
	}
}
