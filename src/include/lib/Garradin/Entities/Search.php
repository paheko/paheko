<?php

namespace Garradin\Entities;

use Garradin\AdvancedSearch;
use Garradin\DB;
use Garradin\Entity;

use Garradin\Accounting\AdvancedSearch as Accounting_AdvancedSearch;
use Garradin\Users\AdvancedSearch as Users_AdvancedSearch;

use KD2\DB\DB_Exception;

class Search extends Entity
{
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

	const TARGETS = [
		self::TARGET_USERS => 'Membres',
		self::TARGET_ACCOUNTING => 'Comptabilité',
	];

	protected int $id;
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
			$this->assert($db->test('users', 'id = ?', $data['id_user']), 'Numéro de membre inconnu');
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
			$q = json_decode($this->content, true);

			return $this->getAdvancedSearch()->make($q->query, $q->order, $q->desc);
		}
		else {
			throw new \LogicException('SQL search cannot be used as dynamic list');
		}
	}

	public function getAdvancedSearch(): AdvancedSearch
	{
		if ($this->target == self::TARGET_ACCOUNTING) {
			$class = 'Garradin\Accounting\AdvancedSearch';
		}
		else {
			$class = 'Garradin\Users\AdvancedSearch';
		}

		if (null === $this->_as || !is_a($this->_as, $class)) {
			$this->_as = new $class;
		}

		return $this->_as;
	}

	/**
	 * Returns a SQLite3Result for the current search
	 * @param  array  $columns If this array is not empty, then the specified columns will be used
	 */
	protected function query(array $columns = []): \SQLite3Result
	{
		if (null !== $this->_result) {
			return $this->_result;
		}

		$limit = 100;

		if ($this->type == self::TYPE_JSON) {
			$sql = $this->getDynamicList()->SQL();
		}

		if (count($columns)) {
			$sql = preg_replace('/^\s*SELECT.*FROM\s+/Uis', 'SELECT ' . implode(', ', $columns) . ' FROM ', $sql);
			$limit = null;
		}

		$sql = $this->content;

		$has_limit = preg_match('/LIMIT\s+\d+/i', $sql);

		// force LIMIT
		if ($limit && !$has_limit) {
			$sql = preg_replace('/;?\s*$/', '', $sql);
			$sql .= ' LIMIT ' . (int) $limit;
		}
		elseif (!$limit && $has_limit) {
			$sql = preg_replace('/LIMIT\s+.*;?\s*$/', '', $sql);
		}

		$allowed_tables = $this->getProtectedTables();

		try {
			$st = $db->protectSelect($allowed_tables, $sql);

			$this->_result = $st->execute();
			return $this->_result;
		}
		catch (DB_Exception $e) {
			$message = 'Erreur dans la requête : ' . $e->getMessage();

			if (null !== $force_select)
			{
				$message .= "\nVérifiez que votre requête sélectionne bien les colonnes suivantes : " . implode(', ', $force_select);
			}

			throw new UserException($message);
		}
	}

	public function getHeader(): string
	{
		$r = $this->query();
		$columns = [];

		for ($i = 0; $i < $r->numColumns(); $i++) {
			$columns[] = $r->columnName($i);
		}

		return $columns;
	}

	public function iterateResults(array $columns = []): iterable
	{
		$r = $this->query($columns);

		while ($row = $r->fetchArray(\SQLITE3_NUM)) {
			yield $row;
		}
	}

	public function getProtectedTables(): ?array
	{
		if ($this->type != self::TYPE_SQL) {
			return null;
		}

		if ($this->target == self::TARGET_ACCOUNTING) {
			return ['acc_transactions' => null, 'acc_transactions_lines' => null, 'acc_accounts' => null, 'acc_charts' => null, 'acc_years' => null, 'acc_transactions_users' => null];
		}
		else {
			return ['users' => null, 'users_categories' => null];
		}
	}
}
