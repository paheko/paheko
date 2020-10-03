<?php

namespace Garradin\Entities\Accounting;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Account extends Entity
{
	const TABLE = 'acc_accounts';

	// Passif
	const LIABILITY = 1;

	// Actif
	const ASSET = 2;

	// Produit
	const REVENUE = 3;

	// Charge
	const EXPENSE = 4;

	const POSITIONS_NAMES = [
		'',
		'Passif',
		'Actif',
		'Produit',
		'Charge',
	];

	const TYPE_NONE = 0;
	const TYPE_REVENUE = 1;
	const TYPE_EXPENSE = 2;
	const TYPE_BANK = 3;
	const TYPE_CASH = 4;

	/**
	 * Outstanding transaction accounts (like cheque or card payments)
	 */
	const TYPE_OUTSTANDING = 5;

	const TYPE_ANALYTICAL = 6;
	const TYPE_VOLUNTEERING = 7;
	const TYPE_THIRD_PARTY = 8;

	const TYPE_OPENING = 9;
	const TYPE_CLOSING = 10;

	const TYPES_NAMES = [
		'',
		'Recettes',
		'Dépenses',
		'Banque',
		'Caisse',
		'Attente d\'encaissement',
		'Analytique',
		'Bénévolat',
		'Tiers',
		'Ouverture',
		'Clôture',
	];

	protected $id;
	protected $id_chart;
	protected $code;
	protected $label;
	protected $description;
	protected $position;
	protected $type;
	/**
	 * Parent of type (then type needs to be filled)
	 * @var integer either 1 or 0
	 */
	protected $type_parent;
	protected $user;

	protected $_types = [
		'id'          => 'int',
		'id_chart'    => 'int',
		'code'        => 'string',
		'label'       => 'string',
		'description' => '?string',
		'position'    => 'int',
		'type'        => 'int',
		'type_parent' => 'int',
		'user'        => 'int',
	];

	protected $_form_rules = [
		'code'        => 'required|string|alpha_num|max:10',
		'label'       => 'required|string|max:200',
		'description' => 'string|max:2000',
		'position'    => 'required|integer',
		'type'        => 'numeric|min:0',
		'type_parent' => 'numeric|min:0',
	];

	public function getJournal(int $year_id) {
		$db = DB::getInstance();
		$rows = $db->get('SELECT l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE l.id_account = ? AND t.id_year = ?
			ORDER BY t.date;', $this->id(), $year_id);

		$sum = 0;

		foreach ($rows as &$row) {
			$sum += ($row->credit - $row->debit);
			$row->running_sum = $sum;
			$row->date = \DateTime::createFromFormat('Y-m-d', $row->date);
		}

		return $rows;
	}
}