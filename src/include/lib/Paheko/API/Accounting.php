<?php

namespace Paheko\API;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Export;
use Paheko\Accounting\Reports;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Transaction;
use Paheko\APIException;
use Paheko\Utils;

trait Accounting
{
	protected function accounting(string $uri): ?array
	{
		$fn = strtok($uri, '/');
		$p1 = strtok('/');
		$p2 = strtok('');

		if ($fn == 'transaction') {
			if (!$p1) {
				$this->requireMethod('POST');
				$this->requireAccess(Session::ACCESS_WRITE);

				$transaction = new Transaction;
				$transaction->importFromAPI($this->params);
				$transaction->save();

				if (!empty($this->params['linked_users'])) {
					$transaction->updateLinkedUsers((array)$this->params['linked_users']);
				}

				if (!empty($this->params['linked_transactions'])) {
					$transaction->updateLinkedTransactions((array)$this->params['linked_transactions']);
				}

				if (!empty($this->params['linked_subscriptions'])) {
					$transaction->updateSubscriptionLinks((array)$this->params['linked_subscriptions']);
				}

				if ($this->hasParam('move_attachments_from')
					&& $this->isPathAllowed($this->params['move_attachments_from'])) {
					$file = Files::get($this->params['move_attachments_from']);

					if ($file && $file->isDir()) {
						$file->rename($transaction->getAttachementsDirectory());
					}
				}

				return $transaction->asJournalArray();
			}
			// Return or edit linked users
			elseif ($p1 && ctype_digit($p1) && $p2 == 'users') {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method === 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->updateLinkedUsers((array)($_POST['users'] ?? null));
					return self::SUCCESS;
				}
				elseif ($this->method === 'DELETE') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->updateLinkedUsers([]);
					return self::SUCCESS;
				}
				elseif ($this->method === 'GET') {
					return $transaction->listLinkedUsers();
				}
				else {
					throw new APIException('Wrong request method', 405);
				}
			}
			// Return or edit linked subscriptions
			elseif ($p1 && ctype_digit($p1) && $p2 == 'subscriptions') {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method === 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->updateSubscriptionLinks((array)($_POST['subscriptions'] ?? null));
					return self::SUCCESS;
				}
				elseif ($this->method === 'DELETE') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->deleteAllSubscriptionLinks([]);
					return self::SUCCESS;
				}
				elseif ($this->method === 'GET') {
					return $transaction->listSubscriptionLinks();
				}
				else {
					throw new APIException('Wrong request method', 405);
				}
			}
			elseif ($p1 && ctype_digit($p1) && !$p2) {
				$transaction = Transactions::get((int)$p1);

				if (!$transaction) {
					throw new APIException(sprintf('Transaction #%d not found', $p1), 404);
				}

				if ($this->method === 'GET') {
					return $transaction->asJournalArray();
				}
				elseif ($this->method === 'POST') {
					$this->requireAccess(Session::ACCESS_WRITE);
					$transaction->importFromAPI($this->params);
					$transaction->save();

					if (!empty($this->params['linked_users'])) {
						$transaction->updateLinkedUsers((array)$this->params['linked_users']);
					}

					if (!empty($this->params['linked_transactions'])) {
						$transaction->updateLinkedTransactions((array)$this->params['linked_transactions']);
					}

					if (!empty($this->params['linked_subscriptions'])) {
						$transaction->updateSubscriptionLinks((array)$this->params['linked_subscriptions']);
					}

					return $transaction->asJournalArray();
				}
				else {
					throw new APIException('Wrong request method', 400);
				}
			}
			else {
				throw new APIException('Unknown transactions route', 404);
			}
		}
		elseif ($fn == 'charts') {
			$this->requireMethod('GET');

			if ($p1 && ctype_digit($p1) && $p2 === 'accounts') {
				$a = new Accounts((int)$p1);
				return array_map(fn($c) => $c->asArray(), $a->listAll());
			}
			elseif (!$p1 && !$p2) {
				return array_map(fn($c) => $c->asArray(), Charts::list());
			}
			else {
				throw new APIException('Unknown charts action', 404);
			}
		}
		elseif ($fn == 'years') {
			$this->requireMethod('GET');

			if (!$p1 && !$p2) {
				return Years::list();
			}

			$id_year = null;

			if ($p1 === 'current') {
				$id_year = Years::getCurrentOpenYearId();
			}
			elseif ($p1 && ctype_digit($p1)) {
				$id_year = (int)$p1;
			}

			if (!$id_year) {
				throw new APIException('Missing year in request, or no open years exist', 400);
			}

			$year = Years::get($id_year);

			if (!$year) {
				throw new APIException('Invalid year.', 400, $e);
			}

			if ($p2 === 'journal') {
				try {
					return $this->export(Reports::getJournal(['year' => $id_year]));
				}
				catch (\LogicException $e) {
					throw new APIException('Missing parameter for journal: ' . $e->getMessage(), 400, $e);
				}
			}
			elseif (0 === strpos($p2, 'journal/')) {
				$account = substr($p2, strlen('journal/'));
				$a = $year->chart()->accounts();

				if (substr($account, 0, 1) === '=') {
					$account = $a->get(intval(substr($account, 1)));
				}
				else {
					$account = $a->getWithCode($account);
				}

				if (!$account) {
					throw new APIException('Unknown account id or code.', 400, $e);
				}

				$list = $account->listJournal($year->id, false);
				$list->setTitle(sprintf('Journal - %s - %s', $account->code, $account->label));
				$list->loadFromQueryString();
				$list->setPageSize(null);
				$list->orderBy('date', false);
				return $this->export($list->iterate());
			}
			elseif (0 === strpos($p2, 'export/')) {
				strtok($p2, '/');
				$type = strtok('.');
				$format = strtok('') ?: 'json';

				try {
					Export::export($year, $format, $type);
				}
				catch (\InvalidArgumentException $e) {
					throw new APIException($e->getMessage(), 400, $e);
				}

				return null;
			}
			else {
				throw new APIException('Unknown years action', 404);
			}
		}
		else {
			throw new APIException('Unknown accounting action', 404);
		}
	}
}
