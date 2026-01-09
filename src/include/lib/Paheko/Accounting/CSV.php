<?php

namespace Paheko\Accounting;

use Paheko\CSV_Custom;
use KD2\Office\OFXParser;

class CSV extends CSV_Custom
{
	public bool $ofx_balance_as_transaction = false;

	public function loadFile(string $path, string $file_name): void
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

		$this->file_name = $file_name;
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
				$table[] = 'notes';
			}

			$this->setTranslationTable($table);

			$this->skip(0);

			// FIXME: return alert if account number doesn't match current account
			$date_format = 'Y-m-d';

			foreach ($account->statement->transactions as $t) {
				$row = [$t->date->format($date_format), $t->name, $t->amount];

				if ($extended) {
					$row[] = $t->id;
					$row[] = $t->check_number;
					$row[] = $t->memo;
				}


				$this->append($row);
			}

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

			//echo '<pre>'; var_dump($this->csv); $this->clear(); exit;
		}
		catch (\InvalidArgumentException | UserException $e) {
			throw new UserException('Fichier OFX invalide: ' . $e->getmessage(), 400, $e);
		}
	}
}
