<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Line;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Accounting\Year;
use Garradin\CSV_Custom;
use Garradin\DB;
use Garradin\UserException;

class Import
{
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
		];

		$o = (object) array_merge($options_default, $options);

		if ($type != Export::GROUPED && $type != Export::SIMPLE && $type != Export::FEC) {
			throw new \InvalidArgumentException('Invalid type value');
		}

		if ($year->closed) {
			throw new \InvalidArgumentException('Closed year');
		}

		$db = DB::getInstance();
		$db->begin();

		$accounts = $year->accounts();
		$transaction = null;
		$types = array_flip(Transaction::TYPES_NAMES);

		if ($o->return_report) {
			$report = ['created' => [], 'modified' => [], 'unchanged' => []];
		}
		else {
			$report = null;
		}

		$save_transaction = function (Transaction &$transaction) use ($o, &$report, &$lines) {
			// Remove deleted lines
			foreach ($transaction->getLines() as $line) {
				if (!in_array($line, $lines)) {
					$transaction->removeLine($line);
				}
			}

			if (!is_null($report)) {
				if (!$transaction->isModified()) {
					$target = 'unchanged';
				}
				elseif ($transaction->exists()) {
					$target = 'modified';
				}
				else {
					$target = 'created';
				}

				$report[$target][] = $transaction->asJournalArray();
			}

			if (!$o->dry_run) {
				if ($transaction->countLines() > 2) {
					$transaction->type = Transaction::TYPE_ADVANCED;
				}

				$transaction->save();
			}

			$lines = [];
			$transaction = null;
		};

		$l = 1;

		try {
			$current_id = null;
			$lines = [];

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
						$save_transaction($transaction);
					}

					if (!$has_transaction && null === $transaction) {
						throw new UserException('cette ligne n\'est reliée à aucune écriture');
					}
				}
				else {
					if (!empty($row->id) && $row->id != $current_id) {
						$current_id = (int) $row->id;

						if (null !== $transaction) {
							$save_transaction($transaction);
						}
					}

					if (empty($row->type)) {
						$row->type = Transaction::TYPES_NAMES[Transaction::TYPE_ADVANCED];
					}
				}

				// Find or create transaction
				if (null === $transaction) {
					if (!empty($row->id) && !$o->ignore_ids) {
						$transaction = Transactions::get((int)$row->id);

						if (!$transaction) {
							throw new UserException(sprintf('l\'écriture #%d est introuvable', $row->id));
						}

						if ($transaction->id_year != $year->id()) {
							throw new UserException(sprintf('l\'écriture #%d appartient à un autre exercice', $row->id));
						}

						if ($transaction->validated) {
							throw new UserException(sprintf('l\'écriture #%d est validée et ne peut être modifiée', $row->id));
						}
					}
					else {
						$transaction = new Transaction;
						$transaction->id_creator = $user_id;
						$transaction->id_year = $year->id();
					}

					if (!isset($types[$row->type])) {
						throw new UserException(sprintf('le type "%s" est inconnu', $row->type));
					}

					// FEC does not define type, so don't change it
					if (!$transaction->exists() || $type != Export::FEC) {
						$transaction->type = $types[$row->type];
					}

					$fields = array_intersect_key((array)$row, array_flip(['label', 'date', 'notes', 'reference']));

					$transaction->importForm($fields);

					// Set status
					if (!empty($row->status)) {
						$status_list = array_map('trim', explode(',', $row->status));
						$status = 0;

						foreach (Transaction::STATUS_NAMES as $k => $v) {
							if (in_array($v, $status_list)) {
								$status |= $k;
							}
						}

						$transaction->status = $status;
					}
				}

				$data = [];

				if (!empty($row->analytical)) {
					$id_analytical = $accounts->getIdFromCode($row->analytical);

					if (!$id_analytical) {
						throw new UserException(sprintf('le compte analytique "%s" n\'existe pas dans le plan comptable', $row->analytical));
					}

					$data['id_analytical'] = $id_analytical;
				}
				elseif (property_exists($row, 'analytical')) {
					$data['id_analytical'] = null;
				}

				// Add two transaction lines for each CSV line
				if ($type == Export::SIMPLE) {
					$credit_account = $accounts->getIdFromCode($row->credit_account);
					$debit_account = $accounts->getIdFromCode($row->debit_account);

					if (!$credit_account) {
						throw new UserException(sprintf('Compte de crédit "%s" inconnu dans le plan comptable', $row->credit_account));
					}

					if (!$debit_account) {
						throw new UserException(sprintf('Compte de débit "%s" inconnu dans le plan comptable', $row->debit_account));
					}

					$data['reference'] = isset($row->p_reference) ? $row->p_reference : null;

					if (!$transaction->exists()) {
						$l1 = new Line;
						$l2 = new Line;
						$transaction->addLine($l1);
						$transaction->addLine($l2);
					}
					else {
						$lines = $transaction->getLines();

						if (count($lines) != 2) {
							throw new UserException('cette écriture comporte plus de deux lignes et ne peut donc être modifiée par un import simplifié');
						}

						// Find correct debit/credit lines
						if ($lines[0]->credit != 0) {
							$l1 = $lines[0];
							$l2 = $lines[1];
						}
						else {
							$l1 = $lines[1];
							$l2 = $lines[0];
						}
					}

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

					array_push($lines, $l1, $l2);
					$save_transaction($transaction);
					$transaction = null;
				}
				else {
					$id_account = $accounts->getIdFromCode($row->account);

					if (!$id_account) {
						throw new UserException(sprintf('le compte "%s" n\'existe pas dans le plan comptable', $row->account));
					}

					$data = $data + [
						'credit'     => $row->credit ?: 0,
						'debit'      => $row->debit ?: 0,
						'id_account' => $id_account,
						'reference'  => $row->line_reference ?? null,
						'label'      => $row->line_label ?? null,
						'reconciled' => $row->reconciled ?? false,
					];

					if (!empty($row->line_id) && !$o->ignore_ids) {
						$line = $transaction->getLine((int)$row->line_id);

						if (!$line) {
							throw new UserException(sprintf('le numéro de ligne "%s" n\'existe pas dans l\'écriture "%s"', $row->line_id, $transaction->id ?: 'à créer'));
						}
					}
					else {
						$line = new Line;
					}

					$line->importForm($data);
					array_push($lines, $line);

					// If a line_id was supplied, then we already have a Line object.
					// Just changing it is enough, no need to add it to the transaction
					if (!$line->exists()) {
						$transaction->addLine($line);
					}
				}
			}

			if (null !== $transaction) {
				$save_transaction($transaction);
			}
		}
		catch (UserException $e) {
			$db->rollback();
			$e->setMessage(sprintf('Erreur sur la ligne %d : %s', $l, $e->getMessage()));

			if (null !== $transaction) {
				$e->setDetails($transaction->asDetailsArray());
			}

			throw $e;
		}

		$db->commit();

		if ($report) {
			foreach ($report as $type => $entries) {
				$report[$type . '_count'] = count($entries);
			}
		}

		return $report;
	}
}
