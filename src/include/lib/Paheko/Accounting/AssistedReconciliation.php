<?php

namespace Paheko\Accounting;

use Paheko\Accounting\CSV;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Users\Session;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Accounting\Year;

/**
 * Provides assisted reconciliation
 */
class AssistedReconciliation
{
	const COLUMNS = [
		'label'          => 'Libellé',
		'date'           => 'Date',
		//'notes'          => 'Remarques',
		//'reference'      => 'Numéro pièce comptable',
		//'p_reference'    => 'Référence paiement',
		'amount'         => 'Montant',
		'debit'          => 'Débit',
		'credit'         => 'Crédit',
		'balance'        => 'Solde',
	];

	protected $csv;
	protected Account $account;

	public function __construct(Account $account)
	{
		$this->account = $account;
		$this->csv = new CSV(Session::getInstance(), 'acc_reconcile_csv');
		$this->csv->ofx_balance_as_transaction = true;
		$this->csv->setColumns(self::COLUMNS);
		$this->csv->setMandatoryColumns(['label', 'date']);
		$this->csv->setModifier(function (\stdClass $line) use ($account) {
			$date = Utils::parseDateTime($line->date);

			$line->date = $date;

			static $has_amount = null;

			if (null === $has_amount) {
				$has_amount = in_array('amount', $this->csv->getTranslationTable());
			}

			if (!$has_amount && isset($line->credit) && isset($line->debit)) {
				$line->amount = $line->credit ?: '-' . ltrim($line->debit, '- \t\r\n');
			}

			$line->amount = Utils::moneyToInteger($line->amount ?? 0, false);

			if (!empty($line->balance)) {
				$line->balance = (substr($line->balance, 0, 1) == '-' ? -1 : 1) * Utils::moneyToInteger($line->balance, false);
			}

			if (!empty($line->amount)
				&& !preg_match('/^Solde(?:\s+au\s+.*?)$/i', $line->label)) {
				$line->new_params = http_build_query([
					'a00' => abs($line->amount),
					'l' => $line->label,
					'dt' => $date ? $date->format('Y-m-d') : '',
					't' => $line->amount < 0 ? Transaction::TYPE_EXPENSE : Transaction::TYPE_REVENUE,
					'ab' => $account->code,
				]);
			}

			return $line;
		});
	}

	public function csv(): CSV
	{
		return $this->csv;
	}

	public function setSettings(array $translation_table, int $skip): void
	{
		$this->csv->setTranslationTable($translation_table);

		if ((in_array('credit', $translation_table) && !in_array('debit', $translation_table))
			|| (!in_array('credit', $translation_table) && in_array('debit', $translation_table))) {
			$this->csv->clear();
			throw new UserException('Il est nécessaire de sélectionner les deux colonnes "débit" et "crédit", pas seulement "crédit" ou "débit".');
		}

		$this->csv->skip($skip);
	}

	public function getStartAndEndDates(Year $year): ?array
	{
		$start = $end = null;

		if (!$this->csv->ready()) {
			return compact('start', 'end');
		}

		$in_year = 0;

		foreach ($this->csv->iterate() as $line) {
			if (null === $start || $line->date < $start) {
				$start = $line->date;
			}

			if (null === $end || $line->date > $end) {
				$end = $line->date;
			}

			if ($line->date >= $year->start_date && $line->date <= $year->end_date) {
				$in_year++;
			}
		}

		if (!$in_year) {
			throw new UserException('Aucune écriture du fichier ne se situe dans les dates de l\'exercice comptable sélectionné');
		}

		return compact('start', 'end');
	}

	public function mergeJournal(\Generator $journal, \DateTimeInterface $start, \DateTimeInterface $end)
	{
		$lines = [];

		$csv = iterator_to_array($this->csv->iterate());
		$journal = iterator_to_array($journal);
		$i = 0;
		$sum = 0;

		foreach ($journal as $j) {
			$id = $j->date->format('Ymd') . '.' . $i++;

			$row = (object) ['csv' => null, 'journal' => $j];

			if (isset($j->debit)) {
				foreach ($csv as &$line) {
					if (!isset($line->date)) {
						 continue;
					}

					// Match date, amount and label
					if ($j->date->format('Ymd') == $line->date->format('Ymd')
						&& ($j->credit * -1 == $line->amount || $j->debit == $line->amount)
						&& strtolower($j->label) == strtolower($line->label)) {
						$row->csv = $line;
						$line = null;
						break;
					}
				}
			}

			$lines[$id] = $row;
		}

		unset($line, $row, $j);

		// Second round to match only amount and label
		foreach ($lines as $row) {
			if ($row->csv || !isset($row->journal->debit)) {
				continue;
			}

			$j = $row->journal;

			foreach ($csv as &$line) {
				if (!isset($line->date)) {
					 continue;
				}

				if ($line->date < $start || $line->date > $end) {
					continue;
				}

				if ($j->date->format('Ymd') == $line->date->format('Ymd')
					&& ($j->credit * -1 == $line->amount || $j->debit == $line->amount)) {
					$row->csv = $line;
					$line = null;
					break;
				}
			}
		}

		unset($j, $line);

		// Then add CSV lines on the right
		foreach ($csv as $line) {
			if (null == $line || !isset($line->date)) {
				continue;
			}

			if ($line->date < $start || $line->date > $end) {
				continue;
			}

			$id = $line->date->format('Ymd') . '.' . ($i++);
			$lines[$id] = (object) ['csv' => $line, 'journal' => null];
		}

		ksort($lines);
		$prev = null;

		foreach ($lines as &$line) {
			$line->add = false;

			if (isset($line->csv)) {
				$sum += $line->csv->amount;
				$line->csv->running_sum = $sum;

				if ($prev && ($prev->date->format('Ymd') != $line->csv->date->format('Ymd') || $prev->label != $line->csv->label)) {
					$prev = null;
				}
			}

			if (isset($line->csv) && isset($line->journal)) {
				$prev = null;
			}

			if (isset($line->csv, $line->csv->new_params) && !isset($line->journal) && !$prev) {
				$line->add = true;
				$prev = $line->csv;
			}
		}

		return $lines;
	}
}
