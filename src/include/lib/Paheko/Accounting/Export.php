<?php

namespace Paheko\Accounting;

use Paheko\Entities\Accounting\Line;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Accounting\Year;
use Paheko\Config;
use Paheko\CSV;
use Paheko\DB;
use Paheko\Users\DynamicFields;
use Paheko\Utils;

class Export
{
	const FULL = 'full';
	const GROUPED = 'grouped';
	const SIMPLE = 'simple';
	const FEC = 'fec';

	const NAMES = [
		self::FULL => 'Complet',
		self::GROUPED => 'Groupé',
		self::SIMPLE => 'Simplifié',
		self::FEC => 'FEC',
	];

	const COLUMNS_FULL = [
		'Numéro d\'écriture'     => 'id',
		'Type'                   => 'type',
		'Statut'                 => 'status',
		'Libellé'                => 'label',
		'Date'                   => 'date',
		'Remarques'              => 'notes',
		'Numéro pièce comptable' => 'reference',

		// Lines
		'Numéro compte'     => 'account',
		'Libellé compte'    => 'account_label',
		'Débit'             => 'debit',
		'Crédit'            => 'credit',
		'Référence ligne'   => 'line_reference',
		'Libellé ligne'     => 'line_label',
		'Rapprochement'     => 'reconciled',
		'Projet analytique' => 'project',
		'Membres associés'  => 'linked_users',
	];

	const COLUMNS = [
		self::GROUPED => self::COLUMNS_FULL,
		self::FULL => self::COLUMNS_FULL,
		self::SIMPLE => [
			'Numéro d\'écriture'     => 'id',
			'Type'                   => 'type',
			'Statut'                 => 'status',
			'Libellé'                => 'label',
			'Date'                   => 'date',
			'Remarques'              => 'notes',
			'Numéro pièce comptable' => 'reference',
			'Référence paiement'     => 'p_reference',
			'Compte de débit'        => 'debit_account',
			'Compte de crédit'       => 'credit_account',
			'Montant'                => 'amount',
			'Projet analytique'      => 'project',
			'Membres associés'       => 'linked_users',
		],
		self::FEC => [
			'JournalCode'   => null,
			'JournalLib'    => null,
			'EcritureNum'   => 'id',
			'EcritureDate'  => 'date',
			'CompteNum'     => 'account',
			'CompteLib'     => 'account_label',
			'CompAuxNum'    => null,
			'CompAuxLib'    => null,
			'PieceRef'      => 'reference',
			'PieceDate'     => null,
			'EcritureLib'   => 'label',
			'Debit'         => 'debit',
			'Credit'        => 'credit',
			'EcritureLet'   => null,
			'DateLet'       => null,
			'ValidDate'     => null,
			'MontantDevise' => null,
			'Idevise'       => null,
		],
	];

	const MANDATORY_COLUMNS = [
		self::FULL => [
			'id',
			'type',
			'label',
			'date',
			'account',
			'credit',
			'debit',
		],
		self::GROUPED => [
			'type',
			'label',
			'date',
			'account',
			'credit',
			'debit',
		],
		self::SIMPLE => [
			'label',
			'date',
			'credit_account',
			'debit_account',
			'amount'
		],
		self::FEC => [
			'label',
			'date',
			'account',
			'label',
			'debit',
			'credit',
		],
	];

	/**
	 * Return all transactions from year
	 */
	static public function export(Year $year, string $format, string $type): void
	{
		$header = null;

		if (!array_key_exists($type, self::COLUMNS)) {
			throw new \InvalidArgumentException('Unknown type: ' . $type);
		}

		CSV::export(
			$format,
			sprintf('%s - Export comptable - %s - %s', Config::getInstance()->org_name, self::NAMES[$type], $year->label),
			self::iterateExport($year->id(), $type),
			array_keys(self::COLUMNS[$type])
		);
	}

	static public function getExamples(Year $year)
	{
		$out = [];

		foreach (self::NAMES as $type => $label) {
			$i = 0;
			$out[$type] = [array_keys(self::COLUMNS[$type])];

			foreach (self::iterateExport($year->id(), $type) as $row) {
				$out[$type][] = $row;

				if (++$i > 1) {
					break;
				}
			}
		}

		return $out;
	}

	static protected function iterateExport(int $year_id, string $type): \Generator
	{
		$id_field = DynamicFields::getNameFieldsSQL('u');

		if (self::SIMPLE == $type) {
			$sql =  'SELECT t.id, t.type, t.status, t.label, t.date, t.notes, t.reference,
				IFNULL(l1.reference, l2.reference) AS p_reference,
				a1.code AS debit_account,
				a2.code AS credit_account,
				l1.debit AS amount,
				IFNULL(p.code, p.label) AS project,
				GROUP_CONCAT(%s) AS linked_users
				FROM acc_transactions t
				INNER JOIN acc_transactions_lines l1 ON l1.id_transaction = t.id AND l1.debit != 0
				INNER JOIN acc_transactions_lines l2 ON l2.id_transaction = t.id AND l2.credit != 0
				INNER JOIN acc_accounts a1 ON a1.id = l1.id_account
				INNER JOIN acc_accounts a2 ON a2.id = l2.id_account
				LEFT JOIN acc_projects p ON p.id = l1.id_project
				LEFT JOIN acc_transactions_users tu ON tu.id_transaction = t.id
				LEFT JOIN users u ON u.id = tu.id_user
				WHERE t.id_year = ?
					AND t.type != %d
				GROUP BY t.id
				ORDER BY t.date, t.id;';

			$sql = sprintf($sql, $id_field, Transaction::TYPE_ADVANCED);
		}
		elseif (self::FEC == $type) {
			// JournalCode|JournalLib|EcritureNum|EcritureDate|CompteNum|CompteLib
			// |CompAuxNum|CompAuxLib|PieceRef|PieceDate|EcritureLib|Debit|Credit
			// |EcritureLet|DateLet|ValidDate|MontantDevise|Idevise

			$sql = 'SELECT
				printf(\'%02d\', t.type) AS type_id, t.type,
				t.id, t.date,
				a.code AS account, a.label AS account_label,
				NULL AS CompAuxNum, NULL AS CompAuxLib,
				t.reference,
				strftime(\'%Y%m%d\', t.date) AS ref_date,
				t.label,
				l.debit, l.credit,
				NULL AS EcritureLet,
				NULL AS DateLet,
				NULL AS ValidDate,
				NULL AS MontantDevise,
				NULL AS Idevise
				FROM acc_transactions t
				INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
				INNER JOIN acc_accounts a ON a.id = l.id_account
				WHERE t.id_year = ?
				GROUP BY t.id, l.id
				ORDER BY t.date, t.id, l.id;';
		}
		elseif (self::FULL == $type || self::GROUPED == $type) {
			$sql = 'SELECT t.id, t.type, t.status, t.label, t.date, t.notes, t.reference,
				a.code AS account, a.label AS account_label, l.debit AS debit, l.credit AS credit,
				l.reference AS line_reference, l.label AS line_label, l.reconciled,
				IFNULL(p.code, p.label) AS project,
				GROUP_CONCAT(%s) AS linked_users
				FROM acc_transactions t
				INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
				INNER JOIN acc_accounts a ON a.id = l.id_account
				LEFT JOIN acc_projects p ON p.id = l.id_project
				LEFT JOIN acc_transactions_users tu ON tu.id_transaction = t.id
				LEFT JOIN users u ON u.id = tu.id_user
				WHERE t.id_year = ?
				GROUP BY t.id, l.id
				ORDER BY t.date, t.id, l.id;';

			$sql = sprintf($sql, $id_field);
		}
		else {
			throw new \LogicException('Unknown export type: ' . $type);
		}

		$res = DB::getInstance()->iterate($sql, $year_id);

		$previous_id = null;

		foreach ($res as $row) {
			if ($type == self::GROUPED && $previous_id === $row->id) {
				// Remove transaction data to differentiate lines and transactions
				$row->id = $row->type = $row->status = $row->label = $row->date = $row->notes = $row->reference = $row->linked_users = null;
			}
			else {
				$row->type = Transaction::TYPES_NAMES[$row->type];

				if (property_exists($row, 'status')) {
					$status = [];

					foreach (Transaction::STATUS_NAMES as $k => $v) {
						if ($row->status & $k) {
							$status[] = $v;
						}
					}

					$row->status = implode(', ', $status);
				}

				$row->date = \DateTime::createFromFormat('Y-m-d', $row->date);
				$row->date = $row->date->format($type == self::FEC ? 'Ymd' : 'd/m/Y');
				$previous_id = $row->id;
			}

			if ($type == self::SIMPLE) {
				$row->amount = Utils::money_format($row->amount, ',', '');
			}
			else {
				$row->credit = Utils::money_format($row->credit, ',', '');
				$row->debit = Utils::money_format($row->debit, ',', '');
			}

			yield $row;
		}
	}
}
