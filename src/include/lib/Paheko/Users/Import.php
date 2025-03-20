<?php
declare(strict_types=1);

namespace Paheko\Users;

use Paheko\CSV_Custom;
use Paheko\Log;
use Paheko\UserException;

class Import
{
	const MODE_AUTO = 'auto';
	const MODE_CREATE = 'create';
	const MODE_UPDATE= 'update';

	const MODES = [
		self::MODE_AUTO,
		self::MODE_CREATE,
		self::MODE_UPDATE,
	];

	static public function report(CSV_Custom $csv, string $mode, ?Session $session = null): array
	{
		if (!in_array($mode, self::MODES)) {
			throw new \InvalidArgumentException('Invalid import mode: ' . $mode);
		}

		$report = ['created' => [], 'modified' => [], 'unchanged' => [], 'errors' => []];

		$logged_user_id = $session ? $session::getUserId() : null;
		$is_logged = $session ? $session->isLogged() : null;
		$safe_categories = $session ? array_flip(Categories::listAssocSafe($session, false)) : null;

		if ($logged_user_id) {
			$report['has_logged_user'] = false;
		}

		if ($is_logged) {
			$report['has_admin_users'] = false;
		}

		foreach (self::iterateImport($csv, $mode, $safe_categories, $report['errors']) as $line => $user) {
			if ($logged_user_id && $user->id == $logged_user_id) {
				$report['has_logged_user'] = true;
				continue;
			}
			elseif ($is_logged && !$user->canBeModifiedBy($session)) {
				$report['has_admin_users'] = true;
				continue;
			}

			try {
				$user->selfCheck();
			}
			catch (UserException $e) {
				$report['errors'][] = sprintf('Ligne %d (%s) : %s', $line, $user->name(), $e->getMessage());
				continue;
			}

			if (!$user->exists()) {
				$report['created'][] = $user;
			}
			elseif ($user->isModified()) {
				$report['modified'][] = $user;
			}
			else {
				$report['unchanged'][] = $user;
			}
		}

		return $report;
	}

	static public function import(CSV_Custom $csv, string $mode, ?Session $session = null): void
	{
		if (!in_array($mode, self::MODES)) {
			throw new \InvalidArgumentException('Invalid import mode: ' . $mode);
		}

		$logged_user_id = $session ? $session::getUserId() : null;
		$is_logged = $session ? $session->isLogged() : null;
		$safe_categories = $session ? array_flip(Categories::listAssocSafe($session, false)) : null;

		$number_field = DynamicFields::getNumberField();
		$db = DB::getInstance();
		$db->begin();

		Log::add(Log::MESSAGE, ['message' => 'Import de membres'], $session ? $session->user()->id : null);

		foreach (self::iterateImport($csv, $mode, $safe_categories) as $i => $user) {
			// Skip logged user, to avoid changing own login field
			if ($logged_user_id && $user->id == $logged_user_id) {
				continue;
			}
			elseif ($is_logged && !$user->canBeModifiedBy($session)) {
				continue;
			}

			try {
				if (empty($user->$number_field)) {
					$user->$number_field = null;
				}

				if ($mode === 'create' || empty($user->$number_field)) {
					$user->setNumberIfEmpty();
				}

				$user->save();
			}
			catch (UserException $e) {
				throw new UserException(sprintf('Ligne %d : %s', $i, $e->getMessage()), 0, $e);
			}
		}

		$db->commit();
	}

	static public function iterateImport(CSV_Custom $csv, string $mode, ?array $safe_categories, ?array &$errors = null): \Generator
	{
		if (!in_array($mode, self::MODES)) {
			throw new \InvalidArgumentException('Invalid import mode: ' . $mode);
		}

		$number_field = DynamicFields::getNumberField();

		foreach ($csv->iterate() as $i => $row) {
			$user = null;

			try {
				if ($mode === 'update') {
					if (empty($row->$number_field)) {
						throw new UserException('Aucun numéro de membre n\'a été indiqué');
					}

					$user = Users::getFromNumber($row->$number_field);

					if (!$user) {
						$msg = sprintf('Le membre avec le numéro "%s" n\'existe pas.', $row->$number_field);
						throw new UserException($msg);
					}
				}
				elseif ($mode === 'auto' && !empty($row->$number_field)) {
					$user = Users::getFromNumber($row->$number_field);
				}

				if (!$user) {
					$user = Users::create();
				}

				$user->importForm((array)$row);

				// Set category, if safe to do so
				if (!empty($row->category) && $safe_categories !== null) {
					if (array_key_exists($row->category, $safe_categories)) {
						$user->set('id_category', $safe_categories[$row->category]);
					}
					else {
						throw new UserException(sprintf('La catégorie "%s" n\'existe pas ou n\'est pas autorisée pour les imports.', $row->category));
					}
				}

				yield $i => $user;
			}
			catch (UserException $e) {
				if (null !== $errors) {
					$errors[] = sprintf('Ligne %d : %s', $i, $e->getMessage());
					continue;
				}

				throw $e;
			}
		}
	}
}
