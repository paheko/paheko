<?php

namespace Paheko\Accounting;

use Paheko\DynamicList;
use Paheko\Users\DynamicFields;
use Paheko\AdvancedSearch as A_S;
use Paheko\DB;
use Paheko\Utils;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Transaction;

use function Paheko\qg;

class AdvancedSearch extends A_S
{
	/**
	 * Returns list of columns for search
	 * @return array
	 */
	public function columns(): array
	{
		$db = DB::getInstance();

		$types = 'CASE t.type ';

		foreach (Transaction::TYPES_NAMES as $num => $name) {
			$types .= sprintf('WHEN %d THEN %s ', $num, $db->quote($name));
		}

		$types .= 'END';

		return [
			'id' => [
				'label'    => 'Numéro écriture',
				'type'     => 'integer',
				'null'     => false,
				'select'   => 't.id',
			],
			'id_line' => [
				'select'   => 'l.id',
			],
			'date' => [
				'label'    => 'Date',
				'type'     => 'date',
				'null'     => false,
				'select'   => 't.date',
			],
			'label' => [
				'label'    => 'Libellé écriture',
				'type'     => 'text',
				'null'     => false,
				'select'   => 't.label',
				'order'    => 't.label COLLATE U_NOCASE %s',
			],
			'reference' => [
				'label'    => 'Numéro pièce comptable',
				'type'     => 'text',
				'null'     => true,
				'select'   => 't.reference',
				'order'    => 't.reference COLLATE U_NOCASE %s',
			],
			'notes' => [
				'label'    => 'Remarques',
				'type'     => 'text',
				'null'     => true,
				'select'   => 't.notes',
				'order'    => 't.notes COLLATE U_NOCASE %s',
			],
			'account_code' => [
				'textMatch'=> true,
				'label'    => 'Numéro de compte',
				'type'     => 'text',
				'null'     => false,
				'select'   => 'a.code',
			],
			'debit' => [
				'label'    => 'Débit',
				'type'     => 'text',
				'null'     => false,
				'select'   => 'l.debit',
				'normalize' => 'money',
			],
			'credit' => [
				'label'    => 'Crédit',
				'type'     => 'text',
				'null'     => false,
				'select'   => 'l.credit',
				'normalize' => 'money',
			],
			'line_label' => [
				'label'    => 'Libellé ligne',
				'type'     => 'text',
				'null'     => true,
				'select'   => 'l.label',
				'order'    => 'l.label COLLATE U_NOCASE %s',
			],
			'line_reference' => [
				'textMatch'=> true,
				'label'    => 'Référence ligne écriture',
				'type'     => 'text',
				'null'     => true,
				'select'   => 'l.reference',
			],
			'type' => [
				'textMatch'=> false,
				'label'    => 'Type d\'écriture',
				'type'     => 'enum',
				'null'     => false,
				'values'   => Transaction::TYPES_NAMES,
				'select'   => $types,
				'where'    => 't.type %s',
			],
			'id_year' => [
				'textMatch'=> false,
				'label'    => 'Exercice',
				'type'     => 'enum',
				'null'     => false,
				'values'   => $db->getAssoc('SELECT id, label FROM acc_years ORDER BY end_date DESC;'),
				'select'   => 'y.label',
				'where'    => 't.id_year %s',
			],
			'project_code' => [
				'textMatch'=> true,
				'label'    => 'Code projet',
				'type'     => 'text',
				'null'     => true,
				'select'   => 'p.code',
			],
			'project_label' => [
				'textMatch'=> true,
				'label'    => 'Libellé projet',
				'type'     => 'text',
				'null'     => true,
				'select'   => 'p.label',
			],
			'has_linked_transactions' => [
				'type'   => 'boolean',
				'label'  => 'Est liée à des écritures',
				'null'   => false,
				'select' => '(SELECT 1 FROM acc_transactions_links tl WHERE tl.id_transaction = t.id OR tl.id_related = t.id) IS NOT NULL',
				'where'  => '(SELECT 1 FROM acc_transactions_links tl WHERE tl.id_transaction = t.id OR tl.id_related = t.id) IS NOT NULL %s',
			],
			'has_linked_users' => [
				'type'   => 'boolean',
				'label'  => 'Est liée à des membres',
				'null'   => false,
				'select' => '(SELECT 1 FROM acc_transactions_users tu WHERE tu.id_transaction = t.id) IS NOT NULL',
				'where'  => '(SELECT 1 FROM acc_transactions_users tu WHERE tu.id_transaction = t.id) IS NOT NULL %s',
			],
			'has_linked_subscriptions' => [
				'type'   => 'boolean',
				'label'  => 'Est liée à des inscriptions',
				'null'   => false,
				'select' => '(SELECT 1 FROM acc_transactions_users tu WHERE tu.id_transaction = t.id AND tu.id_service_user IS NOT NULL) IS NOT NULL',
				'where'  => '(SELECT 1 FROM acc_transactions_users tu WHERE tu.id_transaction = t.id AND tu.id_service_user IS NOT NULL) IS NOT NULL %s',
			],
			'reconciled' => [
				'type'   => 'boolean',
				'label'  => 'Est rapprochée',
				'null'   => false,
				'select' => 'l.reconciled',
				'where'  => 'l.reconciled %s',
			],
		];
	}

	public function redirect(string $query, array $options = []): bool
	{
		$query = trim($query);
		$id_year = $options['id_year'] ?? null;

		// Match account number
		if ($id_year && preg_match('/^[0-9]+[A-Z]*$/', $query)
			&& ($year = Years::get($id_year))
			&& ($id = (new Accounts($year->id_chart))->getIdFromCode($query))) {
			Utils::redirect(sprintf('!acc/accounts/journal.php?id=%d&year=%d', $id, $id_year));
			return true;
		}
		// Match transaction ID
		elseif (substr($query, 0, 1) === '#' && ctype_digit(substr($query, 1))) {
			Utils::redirect(sprintf('!acc/transactions/details.php?id=%d', (int)substr($query, 1)));
			return true;
		}

		return false;
	}

	public function redirectResult(\stdClass $result): void
	{
		Utils::redirect(sprintf('!acc/transactions/details.php?id=%d', $result->id));
	}

	public function simple(string $text, array $options = []): \stdClass
	{
		$query = [];
		$id_year = $options['id_year'] ?? null;

		$text = trim($text);

		if ($id_year) {
			$query[] = [
				'operator' => 'AND',
				'conditions' => [
					[
						'column'   => 'id_year',
						'operator' => '= ?',
						'values'   => [$id_year],
					],
				],
			];
		}

		// Match number: find transactions per credit or debit
		if (preg_match('/^=\s*\d+([.,]\d+)?$/', $text))
		{
			$text = ltrim($text, "\n\t =");
			$query[] = [
				'operator' => 'OR',
				'conditions' => [
					[
						'column'   => 'debit',
						'operator' => '= ?',
						'values'   => [$text],
					],
					[
						'column'   => 'credit',
						'operator' => '= ?',
						'values'   => [$text],
					],
					[
						'column' => 'label',
						'operator' => '1',
					]
				],
			];
		}
		// Match date
		elseif (preg_match('!^\d{2}/\d{2}/\d{4}$!', $text) && ($d = Utils::get_datetime($text)))
		{
			$query[] = [
				'operator' => 'OR',
				'conditions' => [
					[
						'column'   => 'date',
						'operator' => '= ?',
						'values'   => [$d->format('Y-m-d')],
					],
				],
			];
		}
		// Or search in label or reference
		else
		{
			$operator = 'LIKE %?%';
			$query[] = [
				'operator' => 'OR',
				'conditions' => [
					[
						'column'   => 'label',
						'operator' => $operator,
						'values'   => [$text],
					],
					[
						'column'   => 'reference',
						'operator' => $operator,
						'values'   => [$text],
					],
					[
						'column'   => 'reference',
						'operator' => $operator,
						'values'   => [$text],
					],
				],
			];
		}

		return (object) [
			'groups' => $query,
			'order' => 'id',
			'desc' => true,
		];
	}

	public function schemaTables(): array
	{
		return [
			'acc_transactions' => 'Écritures',
			'acc_transactions_lines' => 'Lignes des écritures',
			'acc_accounts' => 'Comptes des plans comptables',
			'acc_years' => 'Exercices',
			'acc_projects' => 'Projets',
		];
	}

	public function tables(): array
	{
		return array_merge(array_keys($this->schemaTables()), [
			'acc_charts',
			'acc_transactions_users',
			'acc_transactions_links',
		]);
	}

	public function make(string $query): DynamicList
	{
		$tables = 'acc_transactions AS t
			INNER JOIN acc_transactions_lines AS l ON l.id_transaction = t.id
			INNER JOIN acc_accounts AS a ON l.id_account = a.id
			INNER JOIN acc_years AS y ON t.id_year = y.id
			LEFT JOIN acc_projects AS p ON l.id_project = p.id';
		return $this->makeList($query, $tables, 'id', true, ['id', 'id_line', 'account_code', 'debit', 'credit']);
	}

	public function defaults(): \stdClass
	{
		$group = [
			'operator' => 'AND',
			'conditions' => [
				[
					'column'   => 'id_year',
					'operator' => '= ?',
					'values'   => [(int)qg('year') ?: Years::getCurrentOpenYearId()],
				],
				[
					'column'   => 'label',
					'operator' => 'LIKE %?%',
					'values'   => [''],
				],
			],
		];

		if (null !== qg('type')) {
			$group['conditions'][] = [
				'column' => 'type',
				'operator' => '= ?',
				'values' => [(int)qg('type')],
			];
		}

		if (null !== qg('account')) {
			$group['conditions'][] = [
				'column' => 'account_code',
				'operator' => '= ?',
				'values' => [qg('account')],
			];
		}

		return (object) ['groups' => [$group]];
	}
}
