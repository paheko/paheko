<?php

namespace Garradin;

use Garradin\Config;
use Garradin\DB;

class Log
{
	/**
	 * How many seconds in the past should we look for failed attempts?
	 * @type int
	 */
	const LOCKOUT_DELAY = 5*60;

	/**
	 * Number of maximum login attempts per minute
	 * 1 = max. 1 attempt per minute
	 * 1.2 = max 6 attempts in 5 minutes
	 * @type float
	 */
	const LOCKOUT_RATE = 1.2;

	const LOGIN_FAIL = 1;
	const LOGIN_SUCCESS = 2;
	const LOGIN_REMIND = 3;
	const LOGIN_CHANGE = 4;

	const DELETE = 10;
	const CREATE = 11;
	const EDIT = 12;

	const ACTIONS = [
		self::LOGIN_FAIL => 'Connexion refusée',
		self::LOGIN_SUCCESS => 'Connexion réussie',
		self::LOGIN_REMIND => 'Rappel de mot de passe',
		self::LOGIN_CHANGE => 'Modification de mot de passe',
		self::DELETE => 'Suppression',
		self::CREATE => 'Création',
		self::EDIT => 'Modification',
	];

	static public function add(int $type, ?string $details = null): void
	{
		if ($type != LOGIN_FAIL) {
			$keep = Config::getInstance()->log_retention;

			// Don't log anything
			if ($keep == 0) {
				return;
			}
		}

		$ip = Utils::getIP();
		$session = Session::getInstance();
		$id_user = $session->isLogged() ? $session->getUser()->id : null;

		DB::getInstance()->insert('log', [
			'id_user'    => $user_id,
			'type'       => $type,
			'details'    => $details,
			'ip_address' => $ip,
		]);
	}

	static public function clean(): void
	{
		$config = Config::getInstance();
		$db = DB::getInstance();

		$days_delete = $config->log_retention;
		$days_anonymous = $config->log_anonymize;

		// Anonymize old logs according to configuration
		$db->exec(sprintf('UPDATE logs SET ip_address = NULL, id_user = NULL
			WHERE type != %d AND type != %d AND created < datetime(\'now\', \'-%d days\');',
			self::LOGIN_FAIL, self::LOGIN_REMIND, $days_anonymous));

		// Delete old logs according to configuration
		$db->exec(sprintf('DELETE FROM logs
			WHERE type != %d AND type != %d AND created < datetime(\'now\', \'-%d days\');',
			self::LOGIN_FAIL, self::LOGIN_REMIND, $days_delete));

		// Delete failed login attempts and reminders after 30 days
		$db->exec(sprintf('DELETE FROM logs WHERE type = %d OR type = %d AND created < datetime(\'now\', \'-%d days\');',
			self::LOGIN_FAIL, self::LOGIN_REMIND, 30));
	}

	/**
	 * Returns TRUE if the current IP address has done too many failed login attempts
	 * @return boolean
	 */
	static public function isLocked(): bool
	{
		$ip = Utils::getIP();

		// is IP locked out?
		$sql = sprintf('SELECT COUNT(*) FROM logs WHERE type = ? AND ip_address = ? AND created > datetime(\'now\', \'-%d seconds\');', self::LOCKOUT_DELAY);
		$count = $db->firstColumn($sql, self::LOGIN_FAIL, $ip);

		if (($count / self::LOCKOUT_DELAY) > self::LOCKOUT_RATE) {
			return true;
		}

		return false;
	}

	static public function list(): DynamicList
	{
		$config = Config::getInstance();

		$columns = [
			'id_user' => [
			],
			'identity' => [
				'label' => 'Membre',
				'select' => 'users.' . $config->champ_identite,
			],
			'created' => [
				'label' => 'Date'
			],
			'type' => [
				'label' => 'Action',
			],
			'ip_address' => [
				'label' => 'Adresse IP',
			],
			'details' => [
				'label' => 'Détails',
			],
		];

		$tables = 'logs LEFT JOIN users ON users.id = logs.id_user';

		$list = new DynamicList($columns, $tables);
		$list->orderBy('created', true);
		$list->setCount('COUNT(logs.id)');
		$list->setModifier(function (&$row) {
			$row->created = \DateTime::createFromFormat('!Y-m-d H:i:s', $row->created);
		});

		return $list;
	}
}
