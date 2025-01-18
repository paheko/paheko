<?php

namespace Paheko\Accounting;

use Paheko\Entities\Accounting\Line;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Accounting\Year;
use Paheko\CSV_Custom;
use Paheko\Users\DynamicFields;
use Paheko\DB;
use Paheko\Log;
use Paheko\UserException;
use Paheko\ValidationException;

use KD2\SimpleDiff;

class Import
{
	static protected function saveImportedTransaction(Transaction $transaction, ?array $linked_users, bool $dry_run = false, ?array &$report = null): void
	{
		static $users = [];
		$found_users = null;

		// Associate users
		if (is_array($linked_users) && count($linked_users)) {
			$found_users = array_intersect_key($users, array_flip($linked_users));

			foreach ($linked_users as $name) {
				if (!array_key_exists($name, $users)) {
					continue;
				}

				$found_users[$name] = $users[$name];
			}

			if (count($found_users) != count($linked_users)) {
				$db = DB::getInstance();
				$id_field = DynamicFields::getNameFieldsSQL();

				// Fetch users by name
				$linked_users_sql = array_filter($linked_users, fn($a) => !ctype_digit($a));
				$linked_users_sql = array_map([$db, 'quote'], $linked_users_sql);
				$linked_users_sql = implode(',', $linked_users_sql);

				$number_field = DynamicFields::getNumberFieldSQL();
				$linked_numbers = array_filter($linked_users, 'ctype_digit');
				$linked_numbers_sql = '';

				// Fetch also users by number
				if (count($linked_numbers)) {
					$linked_numbers = array_map('intval', $linked_numbers);
					$linked_numbers_sql = array_map([$db, 'quote'], $linked_numbers);
					$linked_numbers_sql = implode(',', $linked_numbers_sql);
					$linked_numbers_sql = sprintf(' OR %s IN (%s)', $number_field, $linked_numbers_sql);
				}

				$sql = sprintf('SELECT %s AS name, %s AS number, id
					FROM users WHERE %1$s IN (%s) %s;',
					$id_field,
					$number_field,
					$linked_users_sql,
					$linked_numbers_sql
				);

				foreach ($db->iterate($sql) as $row) {
					$found_users[$row->name]
						= $users[$row->name]
						= $found_users[$row->number]
						= $users[$row->number]
						= $row->id;
				}

				// Fill array with NULL for missing user names, so that we won't go fetch them again
				foreach ($linked_users as $name) {
					if (!array_key_exists($name, $users)) {
						$users[$name] = null;
					}
				}
			}

			$found_users = array_filter($found_users);
		}
		elseif (is_array($linked_users) && count($linked_users) == 0) {
			$found_users = [];
		}


		if ($transaction->countLines() > 2) {
			$transaction->type = Transaction::TYPE_ADVANCED;
		}
		// Try to magically find out what kind of transaction this is
		elseif (!isset($transaction->type)) {
			$transaction->type = $transaction->findTypeFromAccounts();
		}

		if (!$dry_run) {
			if ($transaction->isModified() || $transaction->diff()) {
				$transaction->save();
			}

			if (null !== $found_users) {
				$transaction->updateLinkedUsers($found_users);
			}
		}
		else {
			$transaction->selfCheck();
		}

		if (null !== $report) {
			$diff = null;

			if (!$transaction->exists()) {
				$target = 'created';
			}
			elseif (($diff = $transaction->diff())
				|| ($linked_users = $transaction->listLinkedUsersAssoc()) && (array_values($linked_users) != array_keys($found_users))) {
				if (!$diff) {
					$diff = [];
				}

				$target = 'modified';

				if (array_values($linked_users) != array_keys($found_users)) {
					$diff['linked_users'] = [
						implode(', ', $linked_users),
						implode(', ', array_keys($found_users))
					];
				}

				$linked_users = implode(', ', $linked_users);
				$diff = compact('diff', 'transaction', 'linked_users');
			}
			else {
				$target = 'unchanged';
			}

			$report[$target][] = $diff ?? array_merge($transaction->asJournalArray(), ['linked_users' => implode(', ', array_keys($found_users))]);
		}
	}

	/**
	 * Imports a CSV file of transactions in a year
	 * @param  string     $type    Type of CSV format
	 * @param  Year       $year    Target year where transactions should be updated or created
	 * @param  CSV_Custom $csv     CSV object
	 * @param  int        $user_id Current user ID, the one running the import
	 * @param  array      $options array of options
	 * @return ?array
	 */
	static public function import(string $type, Year $year, CSV_Custom $csv, int $user_id, array $options = []): ?array
	{
		$options_default = [
			'ignore_ids'      => false,
			'dry_run'         => false,
			'return_report'   => false,
			'auto_create_accounts' => false,
		];

		$o = (object) array_merge($options_default, $options);

		$dry_run = $o->dry_run;

		if (!array_key_exists($type, Export::MANDATORY_COLUMNS)) {
			throw new \InvalidArgumentException('Invalid type value');
		}

		$year->assertCanBeModified();

		$db = DB::getInstance();
		$db->begin();
		Log::add(Log::MESSAGE, ['message' => 'Import d\'écritures comptables'], $user_id);

		$accounts = $year->accounts();
		$transaction = null;
		$linked_users = null;
		$types = array_flip(Transaction::TYPES_NAMES);
		$group = $csv->hasSelectedColumn('id') ? 'id' : 'reference';

		if ($o->return_report) {
			$report = ['created' => [], 'modified' => [], 'unchanged' => [], 'accounts' => []];
		}
		else {
			$report = null;
		}

		$l = 1;

		try {
			$current_id = null;

			foreach ($csv->iterate() as $l => $row) {
				$row = (object) $row;

				// Import grouped transactions
				if ($type == Export::GROUPED) {
					// If a line doesn't have any transaction info: this is a line following the previous transaction
					$has_transaction = !(empty($row->id)
						&& empty($row->type)
						&& empty($row->status)
						&& empty($row->label)
						&& empty($row->date)
						&& empty($row->notes)
						&& empty($row->reference)
					);

					// New transaction, save previous one
					if (null !== $transaction && $has_transaction) {
						self::saveImportedTransaction($transaction, $linked_users, $dry_run, $report);
						$transaction = null;
						$linked_users = null;
					}

					if (!$has_transaction && null === $transaction) {
						throw new UserException('cette ligne n\'est reliée à aucune écriture');
					}
				}
				else {
					if (!empty($row->$group) && $row->$group != $current_id) {
						if (null !== $transaction) {
							self::saveImportedTransaction($transaction, $linked_users, $dry_run, $report);
							$transaction = null;
							$linked_users = null;
						}

						$current_id = $row->$group;
					}
				}

				// Find or create transaction
				if (null === $transaction) {
					if (!empty($row->id) && !$o->ignore_ids) {
						// Make sure we remove any weird stuff from transaction ID
						$row_id = preg_replace('/[^\d]/', '', $row->id);
						$transaction = Transactions::get((int)$row_id);

						if (!$transaction) {
							throw new UserException(sprintf('l\'écriture #%d est introuvable', $row_id));
						}

						if ($transaction->id_year != $year->id()) {
							throw new UserException(sprintf('l\'écriture #%d appartient à un autre exercice', $row_id));
						}

						if ($transaction->isLocked()) {
							throw new UserException(sprintf('l\'écriture #%d est validée et ne peut être modifiée', $row_id));
						}

						if ($type !== Export::SIMPLE) {
							$transaction->resetLines();
						}
					}
					else {
						$transaction = new Transaction;
						$transaction->id_creator = $user_id;
						$transaction->id_year = $year->id();
					}

					if (isset($row->type) && !isset($types[$row->type])) {
						throw new UserException(sprintf('le type "%s" est inconnu. Les types reconnus sont : %s.', $row->type, implode(', ', array_keys($types))));
					}

					// FEC does not define type, so don't change it
					if (isset($row->type)) {
						$transaction->type = $types[$row->type];
					}

					$fields = array_intersect_key((array)$row, array_flip(['label', 'date', 'notes', 'reference']));

					// Remove empty values
					$fields = array_filter($fields);

					$transaction->importForm($fields);

					// Don't consider notes field as changed if it only removes line breaks (eg. conversion to CSV removed line breaks)
					if ($transaction->isModified('notes')
						&& $transaction->exists()
						&& str_replace(["\r", "\n"], '', $transaction->getModifiedProperty('notes')) === $row->notes) {
						$transaction->clearModifiedProperties(['notes']);
					}

					// Set status
					if (!empty($row->status)) {
						$status_list = array_map('trim', explode(',', $row->status));
						$status = 0;

						foreach (Transaction::STATUS_NAMES as $k => $v) {
							if (in_array($v, $status_list)) {
								$status |= $k;
							}
						}

						$transaction->set('status', $status);
					}

					if (isset($row->linked_users) && trim($row->linked_users) !== '') {
						$linked_users = array_map('trim', explode(',', $row->linked_users));
					}
					else {
						$linked_users = [];
					}
				}

				$data = [];

				if (!empty($row->project)) {
					$id_project = Projects::getIdFromCodeOrLabel($row->project);

					if (!$id_project) {
						throw new UserException(sprintf('le projet analytique "%s" n\'existe pas', $row->project));
					}

					$data['id_project'] = $id_project;
				}
				elseif (property_exists($row, 'project')) {
					$data['id_project'] = null;
				}

				// Add two transaction lines for each CSV line
				if ($type == Export::SIMPLE) {
					if (empty($row->credit_account)) {
						throw new UserException('Compte de crédit non renseigné');
					}

					if (empty($row->debit_account)) {
						throw new UserException('Compte de crédit non renseigné');
					}

					$credit_account = $accounts->getIdFromCode($row->credit_account);
					$debit_account = $accounts->getIdFromCode($row->debit_account);

					if (!$credit_account) {
						throw new UserException(sprintf('Compte de crédit "%s" inconnu dans le plan comptable', $row->credit_account));
					}

					if (!$debit_account) {
						throw new UserException(sprintf('Compte de débit "%s" inconnu dans le plan comptable', $row->debit_account));
					}

					$data['reference'] = isset($row->p_reference) ? $row->p_reference : null;

					$l1 = $transaction->getCreditLine() ?? new Line;
					$l2 = $transaction->getDebitLine() ?? new Line;

					$l1->importForm($data + [
						'credit'     => $row->amount,
						'debit'      => 0,
						'id_account' => $credit_account,
					]);

					$l2->importForm($data + [
						'credit'     => 0,
						'debit'      => $row->amount,
						'id_account' => $debit_account,
					]);

					if (!$l1->exists()) {
						$transaction->addLine($l1);
					}

					if (!$l2->exists()) {
						$transaction->addLine($l2);
					}

					self::saveImportedTransaction($transaction, $linked_users, $dry_run, $report);
					$transaction = null;
					$linked_users = null;
				}
				else {
					$id_account = $accounts->getIdFromCode($row->account);

					if (!$id_account && $row->account && $o->auto_create_accounts) {
						$account = $accounts->createAuto($row->account, $row->account_label ?? $row->account . ' — Compte créé automatiquement');
						$account->save();
						$id_account = $account->id();

						if ($report !== null) {
							$report['accounts'][] = $account;
						}
					}
					elseif (!$id_account) {
						throw new UserException(sprintf('le compte "%s" n\'existe pas dans le plan comptable', $row->account));
					}

					$line_label = $row->line_label ?? null;
					$line_reference = $row->line_reference ?? null;

					// Try to have a line label, if there is no line label but there is a label
					// This is actually important for imports where there is only a line label, but it is used as a generic label
					if (null === $line_label && isset($row->label)) {
						$line_label = $row->label;
					}

					// Try to use reference as line reference, if it changes from line to line
					if (null === $line_reference && isset($row->reference) && $row->reference != $transaction->reference) {
						$line_reference = $row->reference;
					}

					$data = $data + [
						'credit'     => $row->credit ?: 0,
						'debit'      => $row->debit ?: 0,
						'id_account' => $id_account,
						'reference'  => $line_reference,
						'label'      => $line_label,
						'reconciled' => $row->reconciled ?? false,
					];

					$line = new Line;
					$line->importForm($data);

					if (!$line->credit && !$line->debit) {
						continue;
					}

					$transaction->addLine($line);
				}
			}

			if (null !== $transaction) {
				self::saveImportedTransaction($transaction, $linked_users, $dry_run, $report);
				$transaction = null;
				$linked_users = null;
			}
		}
		catch (UserException $e) {
			$db->rollback();
			$l -= 1; // Decrement line number, as when we reach this, it has been incremented?
			$e->setMessage(sprintf('Erreur sur la ligne %d : %s', $l, $e->getMessage()));

			if (null !== $transaction) {
				$e->setDetails($transaction->asDetailsArray());
			}

			throw $e;
		}

		if ($dry_run) {
			$db->rollback();
		}
		else {
			$db->commit();
		}

		if ($report) {
			foreach ($report as $type => $entries) {
				$report[$type . '_count'] = count($entries);
			}
		}

		return $report;
	}
}
