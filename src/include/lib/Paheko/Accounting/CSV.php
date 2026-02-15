<?php

namespace Paheko\Accounting;

use Paheko\Files\Conversion;
use Paheko\Users\Session;
use Paheko\CSV_Custom;
use Paheko\Utils;
use Paheko\UserException;

use KD2\Office\QIFParser;
use KD2\Office\OFXParser;

class CSV extends CSV_Custom
{
	public bool $ofx_balance_as_transaction = false;
	protected ?string $account_number = null;
	protected ?string $bank = null;
	protected ?array $skip_until = null;
	protected ?array $stop_at = null;

	public function __construct(?Session $session = null, ?string $key = null)
	{
		$this->cache_properties[] = 'account_number';
		$this->cache_properties[] = 'bank';
		parent::__construct($session, $key);

		if ($this->bank === 'CM') {
			$this->skip_until = ['Date', 'Valeur', 'Libellé', 'Débit', 'Crédit'];
			$this->stop_at = ['', '', ''];
		}
	}

	public function loadFile(string $path, ?string $file_name = null): void
	{
		$ext = strtolower(substr($file_name, -4));

		if ($ext === '.ofx') {
			$this->loadOFX($path);
		}
		elseif ($ext === '.qif') {
			$this->loadQIF($path);
		}
		else {
			parent::loadFile($path, $file_name);
		}

		// XLSX from Crédit Mutuel
		if (isset($this->sheets)
			&& count($this->sheets) >= 2
			&& $this->sheets[0] === 'Vos comptes') {
			$this->bank = 'CM';
			$this->setTranslationTable(['date', null, 'label', 'debit', 'credit', null, null]);
			$this->skip(0);

			// Remove first sheet, it's just a list of accounts
			unset($this->rows[0], $this->sheets[0]);

			if (count($this->sheets) === 1) {
				$this->sheet = 1;
			}
		}

		$this->file_name = $file_name;
	}

	/**
	 * Custom CSV parser, try to match some bank accounts
	 */
	protected function parseLine(int $line, array &$row): int
	{
		//$this->clear();
		if (null !== $this->skip_until) {
			foreach ($this->skip_until as $key => $value) {
				if (!array_key_exists($key, $row) || $row[$key] !== $value) {
					return 0;
				}
			}

			$this->skip_until = null;
			return 0;
		}
		elseif (null !== $this->stop_at) {
			$stop = true;

			foreach ($this->stop_at as $key => $value) {
				if (!array_key_exists($key, $row) || $row[$key] !== $value) {
					$stop = false;
					break;
				}
			}

			if ($stop) {
				return -1;
			}
		}

		if (null === $this->skip_until) {
			return 1;
		}
		// Crédit mutuel : fill account number
		elseif ($this->bank === 'CM'
			&& preg_match('/R.I.B. : ([\d\s]+)/', $row[0], $match)) {
			$this->account_number = preg_replace('/\s+/', '', $match[1]);
		}

		return 0;
	}

	protected function loadQIF(string $path)
	{
		$transactions = (new QIFParser)->parse(file_get_contents($path));

		$table = ['date', 'label', 'amount'];
		$extended = false;

		if (array_key_exists('reference', $this->columns)
			&& array_key_exists('p_reference', $this->columns)
			&& array_key_exists('notes', $this->columns)) {
			$extended = true;
			$table[] = 'p_reference';
		}

		$this->setTranslationTable($table);
		$this->skip(0);
		$this->sheet = 0;
		$date_format = 'Y-m-d';

		foreach ($transactions as $t) {
			// In most banks, memo is mostly the second line of the transaction label… that sucks
			$label = trim(sprintf('%s %s', (string)$t->label, (string)$t->memo));

			$row = [$t->date->format($date_format), $label, $t->amount];

			if ($extended) {
				$row[] = $t->check_number;
			}

			$this->append($row);
		}

		// Make sure list is ordered by date, as some banks have weird ordering
		$this->orderBy('date');
	}

	protected function loadOFX(string $path): void
	{
		try {
			$ofx = (new OFXParser)->parse(file_get_contents($path));
			$account = $ofx->accounts[0] ?? null; // always pick first account, even if there are multiple ones

			if (!$account) {
				throw new UserException('aucun compte n\'a été trouvé');
			}

			$table = ['date', 'label', 'amount'];
			$extended = false;

			if (array_key_exists('reference', $this->columns)
				&& array_key_exists('p_reference', $this->columns)
				&& array_key_exists('notes', $this->columns)) {
				$extended = true;
				$table[] = 'reference';
				$table[] = 'p_reference';
			}

			$this->setTranslationTable($table);
			$this->skip(0);
			$this->sheet = 0;
			$date_format = 'Y-m-d';

			// This is to alert if account number doesn't match current account
			$this->account_number = $account->full_number;

			foreach ($account->statement->transactions as $t) {
				// In most banks, memo is mostly the second line of the transaction label… that sucks
				$label = trim(sprintf('%s %s', (string)$t->label, (string)$t->memo));
				$row = [$t->date->format($date_format), $label, $t->amount];

				if ($extended) {
					$row[] = $t->id;
					$row[] = $t->check_number;
				}

				$this->append($row);
			}

			// Make sure list is ordered by date, as some banks have weird ordering
			$this->orderBy('date');

			if ($this->ofx_balance_as_transaction
				&& !$extended) {
				if (isset($account->statement->end)) {
					$this->append([
						$account->statement->end->format($date_format),
						'Fin du relevé',
						'',
					]);
				}

				if (isset($account->balance, $account->balance_date)) {
					$this->append([
						$account->balance_date->format($date_format),
						'Solde',
						$account->balance,
					]);
				}
				elseif (isset($account->available_balance, $account->available_balance_date)) {
					$this->append([
						$account->available_balance_date->format($date_format),
						'Solde',
						$account->available_balance,
					]);
				}

				if (isset($account->statement->start)) {
					$this->prepend([
						$account->statement->start->format($date_format),
						'Début du relevé',
						'',
					]);
				}
			}
		}
		catch (\InvalidArgumentException | UserException $e) {
			throw new UserException('Fichier OFX invalide: ' . $e->getmessage(), 400, $e);
		}
	}
}
