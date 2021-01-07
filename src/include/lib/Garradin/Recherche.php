<?php

namespace Garradin;

use Garradin\Entities\Accounting\Transaction;

class Recherche
{
	const TYPE_JSON = 'json';
	const TYPE_SQL = 'sql';
	const TYPE_SQL_UNPROTECTED = 'sql_unprotected';

	const TARGETS = [
		'membres',
		'compta',
	];

	protected function _checkFields($data)
	{
		$db = DB::getInstance();

		if (array_key_exists('intitule', $data) && trim($data['intitule']) === '')
		{
			throw new UserException('Le champ intitulé ne peut être vide.');
		}

		if (array_key_exists('id_membre', $data) && null !== $data['id_membre'])
		{
			$data['id_membre'] = (int)$data['id_membre'];
		}

		if (array_key_exists('id_membre', $data) && null !== $data['id_membre'] && !$db->test('membres', 'id = ?', $data['id_membre']))
		{
			throw new \InvalidArgumentException('Numéro d\'utilisateur inconnu.');
		}

		static $types = [self::TYPE_SQL, self::TYPE_JSON, self::TYPE_SQL_UNPROTECTED];

		if (array_key_exists('type', $data) && !in_array($data['type'], $types))
		{
			throw new \InvalidArgumentException('Type de recherche inconnu.');
		}

		if (array_key_exists('cible', $data) && !in_array($data['cible'], self::TARGETS, true))
		{
			throw new \InvalidArgumentException('Cible de recherche invalide.');
		}

		$query = null;

		if (array_key_exists('type', $data))
		{
			if (empty($data['contenu']))
			{
				throw new UserException('Le contenu ne peut être vide.');
			}

			if ($data['type'] == self::TYPE_SQL && !is_string($data['contenu']))
			{
				throw new \InvalidArgumentException('Recherche invalide pour le type SQL');
			}

			$query = $data['contenu'];

			if ($data['type']  == self::TYPE_JSON)
			{
				if (!is_object($query))
				{
					throw new \InvalidArgumentException('Recherche invalide pour le type JSON');
				}

				$query = json_encode($query);

				if (!json_decode($query))
				{
					throw new \InvalidArgumentException('JSON invalide pour le type JSON');
				}
			}
		}

		return $query;
	}

	public function duplicate(int $id)
	{
		DB::getInstance()->preparedQuery('INSERT INTO recherches (id_membre, intitule, cible, type, contenu)
			SELECT id_membre, \'Copie de : \' || intitule, cible, type, contenu FROM recherches WHERE id = ?;', [$id]);
	}

	public function edit($id, $data)
	{
		$allowed = ['intitule', 'id_membre', 'type', 'cible', 'contenu'];

		// Supprimer les champs qui ne sont pas ceux de la BDD
		$data = array_intersect_key($data, array_flip($allowed));

		$query = $this->_checkFields($data);

		if (isset($data['contenu']))
		{
			$data['contenu'] = $query;
		}

		return DB::getInstance()->update('recherches', $data, 'id = ' . (int)$id);
	}

	public function add($intitule, $id_membre, $type, $cible, $contenu)
	{
		$data = compact('intitule', 'id_membre', 'type', 'cible', 'contenu');
		$data['contenu'] = $this->_checkFields($data);

		$db = DB::getInstance();

		$db->insert('recherches', $data);

		return $db->lastInsertRowId();
	}

	public function remove($id)
	{
		return DB::getInstance()->delete('recherches', 'id = ?', (int) $id);
	}

	public function get($id)
	{
		$r = DB::getInstance()->first('SELECT * FROM recherches WHERE id = ?;', (int) $id);

		if ($r && $r->type == self::TYPE_JSON) {
			$r->query = (object) json_decode($r->contenu, true);
		}

		return $r;
	}

	public function getList($id_membre, $cible)
	{
		return DB::getInstance()->get('SELECT id, type, intitule, type, id_membre FROM recherches 
			WHERE (id_membre IS NULL OR id_membre = ?) AND cible = ?
			ORDER BY intitule;', (int)$id_membre, $cible);
	}

	/**
	 * Lancer une recherche enregistrée
	 */
	public function search($id, array $force_select = null, $no_limit = false)
	{
		$search = $this->get($id);

		if (!$search)
		{
			return false;
		}

		if ($search->type == self::TYPE_JSON)
		{
			$query = $search->query;
			$search->contenu = $this->buildQuery($search->cible, $query->query, $query->order, $query->desc, $no_limit ? 10000 : $query->limit);
		}

		$unprotected = $search->type == self::TYPE_SQL_UNPROTECTED;

		return $this->searchSQL($search->cible, $search->contenu, $force_select, $no_limit, $unprotected);
	}

	public function getResultHeader(string $target, array $result)
	{
		if (!count($result)) {
			return [];
		}

		$out = [];
		$columns = $this->getColumns($target);

		foreach (reset($result) as $key => $v) {
			if (substr($key, 0, 1) == '_') {
				continue;
			}

			$label = null;

			foreach ($columns as $ckey => $config) {
				if ($ckey == $key) {
					$label = $config->label;
					break;
				}
				elseif (isset($config->alias) && $config->alias == $key) {
					$key = $config->alias;
					$label = $config->label;
					break;
				}
			}

			if (!$label) {
				$label = $key;
			}

			$out[$key] = $label;
		}

		return $out;
	}

	/**
	 * Renvoie la liste des colonnes d'une cible
	 */
	public function getColumns($target)
	{
		$columns = [];
		$db = DB::getInstance();

		if ($target == 'membres')
		{
			$champs = Config::getInstance()->get('champs_membres');

			$columns['id_categorie'] = (object) [
					'textMatch'=> false,
					'label'    => 'Catégorie',
					'type'     => 'enum',
					'null'     => false,
					'values'   => $db->getAssoc('SELECT id, nom FROM membres_categories ORDER BY nom;'),
				];

			foreach ($champs->getList() as $champ => $config)
			{
				$column = (object) [
					'textMatch'=> $champs->isText($champ),
					'label'    => $config->title,
					'type'     => 'text',
					'null'     => true,
				];

				if ($config->type == 'checkbox')
				{
					$column->type = 'boolean';
				}
				elseif ($config->type == 'select')
				{
					$column->type = 'enum';
					$column->values = array_combine($config->options, $config->options);
				}
				elseif ($config->type == 'multiple')
				{
					$column->type = 'bitwise';
					$column->values = $config->options;
				}
				elseif ($config->type == 'date' || $config->type == 'datetime')
				{
					$column->type = $config->type;
				}
				elseif ($config->type == 'number' || $champ == 'numero')
				{
					$column->type = 'integer';
				}

				if ($config->type == 'tel') {
					$column->originalType = 'tel';
				}

				$columns[$champ] = $column;
			}
		}
		elseif ($target === 'compta') {
			$columns['t.id'] = (object) [
				'textMatch'=> false,
				'label'    => 'Numéro écriture',
				'type'     => 'integer',
				'null'     => false,
				'alias'    => 'transaction_id',
			];

			$columns['t.date'] = (object) [
				'textMatch'=> false,
				'label'    => 'Date',
				'type'     => 'date',
				'null'     => false,
				'alias'    => 'date',
			];

			$columns['t.label'] = (object) [
				'textMatch'=> true,
				'label'    => 'Libellé écriture',
				'type'     => 'text',
				'null'     => false,
				'alias'    => 'label',
			];

			$columns['t.reference'] = (object) [
				'textMatch'=> true,
				'label'    => 'Numéro pièce comptable',
				'type'     => 'text',
				'null'     => true,
				'alias'    => 'reference',
			];

			$columns['t.notes'] = (object) [
				'textMatch'=> true,
				'label'    => 'Remarques',
				'type'     => 'text',
				'null'     => true,
				'alias'    => 'notes',
			];

			$columns['l.label'] = (object) [
				'textMatch'=> true,
				'label'    => 'Libellé ligne',
				'type'     => 'text',
				'null'     => true,
				'alias'    => 'line_label',
			];

			$columns['l.debit'] = (object) [
				'textMatch'=> false,
				'label'    => 'Débit',
				'type'     => 'integer',
				'null'     => false,
				'alias'    => 'debit',
				'originalType' => 'money',
			];

			$columns['l.credit'] = (object) [
				'textMatch'=> false,
				'label'    => 'Crédit',
				'type'     => 'integer',
				'null'     => false,
				'alias'    => 'credit',
				'originalType' => 'money',
			];

			$columns['l.reference'] = (object) [
				'textMatch'=> true,
				'label'    => 'Référence ligne écriture',
				'type'     => 'text',
				'null'     => true,
				'alias'    => 'line_reference',
			];

			$columns['t.type'] = (object) [
				'textMatch'=> false,
				'label'    => 'Type d\'écriture',
				'type'     => 'enum',
				'null'     => false,
				'values'   => Transaction::TYPES_NAMES,
				'alias'    => 'type',
			];

			$columns['a.code'] = (object) [
				'textMatch'=> true,
				'label'    => 'Numéro de compte',
				'type'     => 'text',
				'null'     => false,
				'alias'    => 'code',
			];

			$columns['t.id_year'] = (object) [
				'textMatch'=> false,
				'label'    => 'Exercice',
				'type'     => 'enum',
				'null'     => false,
				'values'   => $db->getAssoc('SELECT id, label FROM acc_years ORDER BY end_date;'),
				'alias'    => 'id_year',
			];

			$columns['a2.code'] = (object) [
				'textMatch'=> true,
				'label'    => 'N° de compte projet',
				'type'     => 'text',
				'null'     => true,
				'alias'    => 'id_analytical',
			];
		}

		return $columns;
	}

	/**
	 * Construire une recherche SQL à partir d'un objet généré par QueryBuilder
	 * @param  string  $target Cible de la requête : membres, compta_journal, etc.
	 * @param  array   $groups Groupes de critères
	 * @param  string  $order  Ordre de tri
	 * @param  boolean $desc   Inverser le tri
	 * @param  integer $limit  Limite
	 * @return string Chaîne SQL
	 */
	public function buildQuery(string $target, array $groups, string $order, bool $desc = false, int $limit = 100)
	{
		if (!in_array($target, self::TARGETS, true))
		{
			throw new \InvalidArgumentException('Cible inconnue : ' . $target);
		}

		$config = Config::getInstance();

		if ($target == 'membres')
		{
			$champs = $config->get('champs_membres');
		}

		$db = DB::getInstance();
		$target_columns = $this->getColumns($target);

		if (!isset($target_columns[$order])) {
			throw new UserException('Colonne de tri inconnue : ' . $order);
		}

		$query_columns = [];

		$query_groups = [];

		static $no_transform_operators = ['IS NULL', 'IS NOT NULL', '= 0', '= 1', '&'];

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

				if (!array_key_exists($condition['column'], $target_columns))
				{
					// Ignorer une condition qui se rapporte à une colonne
					// qui n'existe pas, cas possible si on reprend une recherche
					// après avoir modifié les fiches de membres
					throw new UserException('Cette recherche fait référence à une colonne qui n\'existe pas : ' . $condition['column']);
				}

				$query_columns[] = $condition['column'];
				$column = $target_columns[$condition['column']];

				if ($column->textMatch == 'text' && !in_array($condition['operator'], $no_transform_operators))
				{
					$query = sprintf('transliterate_to_ascii(%s) COLLATE NOCASE %s', $db->quoteIdentifier($condition['column']), $condition['operator']);
				}
				else
				{
					$query = sprintf('%s %s', $db->quoteIdentifier($condition['column']), $condition['operator']);
				}

				$values = isset($condition['values']) ? $condition['values'] : [];

				$values = array_map(['Garradin\Utils', 'transliterateToAscii'], $values);

				if (!empty($column->originalType)) {
					if ($column->originalType == 'tel') {
						// Normaliser le numéro de téléphone
						$values = array_map(['Garradin\Utils', 'normalizePhoneNumber'], $values);
					}
					elseif ($column->originalType == 'money') {
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
			throw new UserException('Aucune clause trouvée dans la recherche.');
		}

		// Ajout du champ identité si pas présent
		if ($target == 'membres')
		{
			$query_columns = array_merge([$config->get('champ_identite')], $query_columns);
		}
		// Ajout de champs compta si pas présents
		elseif ($target == 'compta')
		{
			$query_columns = array_merge(['t.id', 't.date', 't.label', 'l.debit', 'l.credit', 'a.code'], $query_columns);
		}

		$query_columns[] = $order;

		if ($target_columns[$order]->textMatch)
		{
			$order = sprintf('transliterate_to_ascii(%s) COLLATE NOCASE', $db->quoteIdentifier($order));
		}
		else
		{
			$order = $db->quoteIdentifier($order);
		}

		$query_columns = array_unique($query_columns);
		$query_columns = array_map(function ($column) use ($target_columns, $db) {
			if (isset($target_columns[$column]->alias)) {
				return sprintf('%s AS %s', $db->quoteIdentifier($column), $db->quote($target_columns[$column]->alias));
			}
			return $db->quoteIdentifier($column);
		}, $query_columns);

		$query_columns = implode(', ', $query_columns);

		$query_groups = '(' . implode(') AND (', $query_groups) . ')';

		$desc = $desc ? 'DESC' : 'ASC';

		if ('compta' === $target) {
			$sql_query = sprintf('SELECT %s
				FROM acc_transactions AS t
				INNER JOIN acc_transactions_lines AS l ON l.id_transaction = t.id
				INNER JOIN acc_accounts AS a ON l.id_account = a.id
				LEFT JOIN acc_accounts AS a2 ON l.id_analytical = a2.id
				WHERE %s GROUP BY t.id ORDER BY %s %s LIMIT %d;',
				$query_columns, $query_groups, $order, $desc, (int) $limit);
			$sql_query = preg_replace('/"(a|a2|l|t)\./', '"$1"."', $sql_query);
		}
		else if ('membres' === $target) {
			$sql_query = sprintf('SELECT id AS _user_id, %s FROM %s WHERE %s ORDER BY %s %s LIMIT %d;',
				$query_columns, $target, $query_groups, $order, $desc, (int) $limit);
		}
		else {
			$sql_query = sprintf('SELECT id, %s FROM %s WHERE %s ORDER BY %s %s LIMIT %d;',
				$query_columns, $target, $query_groups, $order, $desc, (int) $limit);
		}

		return $sql_query;
	}

	/**
	 * Lancer une recherche SQL
	 */
	public function searchSQL(string $target, $query, array $force_select = null, bool $no_limit = false, bool $unprotected = false)
	{
		if (!in_array($target, self::TARGETS, true))
		{
			throw new \InvalidArgumentException('Cible inconnue : ' . $target);
		}

		if (null !== $force_select)
		{
			$query = preg_replace('/^\s*SELECT.*FROM\s+/Ui', 'SELECT ' . implode(', ', $force_select) . ' FROM ', $query);
		}

		if (!$no_limit && !preg_match('/LIMIT\s+\d+/i', $query))
		{
			$query = preg_replace('/;?\s*$/', '', $query);
			$query .= ' LIMIT 100';
		}

		try {
			$db = DB::getInstance();
			static $allowed = [
				'compta' => ['acc_transactions' => null, 'acc_transactions_lines' => null, 'acc_accounts' => null, 'acc_charts' => null, 'acc_years' => null, 'acc_transactions_users' => null],
				'membres' => ['membres' => null, 'membres_categories' => null],
			];

			if ($unprotected) {
				$allowed_tables = null;
			}
			else {
				$allowed_tables = $allowed[$target];
			}

			$db->protectSelect($allowed_tables, $query);
			return $db->get($query);
		}
		catch (\Exception $e) {
			$message = 'Erreur dans la requête : ' . $e->getMessage();

			if (null !== $force_select)
			{
				$message .= "\nVérifiez que votre requête sélectionne bien les colonnes suivantes : " . implode(', ', $force_select);
			}

			throw new UserException($message);
		}
	}

	public function searchQuery(string $table, $query, $order, $desc = false, $limit = 100)
	{
        $sql_query = $this->buildQuery($table, $query, $order, $desc, $limit);
        return $this->searchSQL($table, $sql_query);
	}

	public function buildSimpleMemberQuery(string $query)
	{
	    $operator = 'LIKE %?%';

	    if (is_numeric(trim($query)))
	    {
	        $column = 'numero';
	        $operator = '= ?';
	    }
	    elseif (strpos($query, '@') !== false)
	    {
	        $column = 'email';
	    }
	    else
	    {
	        $column = Config::getInstance()->get('champ_identite');
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
	    	'query' => $query,
	    	'order' => $column,
	    	'desc' => false,
	    	'limit' => 50,
	    ];
	}

	public function schema(string $target)
	{
		$db = DB::getInstance();

		if ($target == 'membres') {
			$tables = [
				'membres'    => $db->firstColumn('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'membres\';'),
				'categories' => $db->firstColumn('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'membres_categories\';'),
			];
		}
		elseif ($target == 'compta') {
			$tables = [
				'acc_transactions'       => $db->firstColumn('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'acc_transactions\';'),
				'acc_transactions_lines' => $db->firstColumn('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'acc_transactions_lines\';'),
			];
		}
		else {
			throw new \LogicException('Unknown target');
		}

		return $tables;
	}
}
