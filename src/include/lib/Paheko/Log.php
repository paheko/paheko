<?php

namespace Paheko;

use Paheko\Config;
use Paheko\DB;
use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

class Log
{
	/**
	 * How many seconds in the past should we look for failed attempts?
	 * @var int
	 */
	const LOCKOUT_DELAY = 20*60;

	/**
	 * Number of maximum login attempts in that delay
	 * @var int
	 */
	const LOCKOUT_ATTEMPTS = 10;

	const SOFT_LOCKOUT_ATTEMPTS = 3;

	const MESSAGE = 0;

	const LOGIN_FAIL = 1;
	const LOGIN_SUCCESS = 2;
	const LOGIN_RECOVER = 3;
	const LOGIN_PASSWORD_CHANGE = 4;
	const LOGIN_CHANGE = 5;
	const LOGIN_AS = 6;

	const CREATE = 10;
	const DELETE = 11;
	const EDIT = 12;
	const SENT = 13;

	const ACTIONS = [
		self::LOGIN_FAIL => 'Connexion refusée',
		self::LOGIN_SUCCESS => 'Connexion réussie',
		self::LOGIN_RECOVER => 'Mot de passe perdu',
		self::LOGIN_PASSWORD_CHANGE => 'Modification de mot de passe',
		self::LOGIN_CHANGE => 'Modification d\'identifiant',
		self::LOGIN_AS => 'Connexion par un administrateur',

		self::CREATE => 'Création',
		self::DELETE => 'Suppression',
		self::EDIT => 'Modification',
		self::SENT => 'Envoi',

		self::MESSAGE => '',
	];

	static public function add(int $type, ?array $details = null, int $id_user = null): void
	{
		if (defined('Paheko\INSTALL_PROCESS')) {
			return;
		}

		if ($type != self::LOGIN_FAIL) {
			$keep = Config::getInstance()->log_retention;

			// Don't log anything
			if ($keep == 0) {
				return;
			}
		}

		if (isset($details['entity'])) {
			$details['entity'] = str_replace('Paheko\Entities\\', '', $details['entity']);
		}

		$ip = Utils::getIP();
		$session = Session::getInstance();
		$id_user ??= Session::getUserId();

		DB::getInstance()->insert('logs', [
			'id_user'    => $id_user,
			'type'       => $type,
			'details'    => $details ? json_encode($details) : null,
			'ip_address' => $ip,
			'created'    => new \DateTime,
		]);
	}

	static public function clean(): void
	{
		$config = Config::getInstance();
		$db = DB::getInstance();

		$days_delete = $config->log_retention;

		// Delete old logs according to configuration
		$db->exec(sprintf('DELETE FROM logs
			WHERE type != %d AND type != %d AND created < datetime(\'now\', \'-%d days\');',
			self::LOGIN_FAIL, self::LOGIN_RECOVER, $days_delete));

		// Delete failed login attempts and reminders after 30 days
		$db->exec(sprintf('DELETE FROM logs WHERE type = %d OR type = %d AND created < datetime(\'now\', \'-%d days\');',
			self::LOGIN_FAIL, self::LOGIN_RECOVER, 30));
	}

	/**
	 * Returns TRUE if the current IP address has done too many failed login attempts
	 * @return int 1 if banned from logging in, -1 if a captcha should be presented, 0 if no restriction is in place
	 */
	static public function isLocked(): int
	{
		$ip = Utils::getIP();

		// is IP locked out?
		$sql = sprintf('SELECT COUNT(*) FROM logs WHERE type = ? AND ip_address = ? AND created > datetime(\'now\', \'-%d seconds\');', self::LOCKOUT_DELAY);
		$count = DB::getInstance()->firstColumn($sql, self::LOGIN_FAIL, $ip);

		if ($count >= self::LOCKOUT_ATTEMPTS) {
			return 1;
		}

		if ($count >= self::SOFT_LOCKOUT_ATTEMPTS) {
			return -1;
		}

		return 0;
	}

	static public function list(array $params = []): DynamicList
	{
		$id_field = DynamicFields::getNameFieldsSQL('u');

		$columns = [
			'id_user' => [
			],
			'created' => [
				'label' => 'Date'
			],
			'identity' => [
				'label' => isset($params['id_self']) ? null : (isset($params['history']) ? 'Membre à l\'origine de la modification' : 'Membre'),
				'select' => $id_field,
			],
			'type_icon' => [
				'select' => null,
				'order' => null,
				'label' => '',
			],
			'type' => [
				'label' => 'Action',
			],
			'details' => [
				'label' => 'Détails',
			],
			'ip_address' => [
				'label' => 'Adresse IP',
			],
		];

		$tables = 'logs LEFT JOIN users u ON u.id = logs.id_user';

		if (isset($params['id_user'])) {
			$conditions = 'logs.id_user = ' . (int)$params['id_user'];
		}
		elseif (isset($params['id_self'])) {
			$conditions = sprintf('logs.id_user = %d AND type < 10', (int)$params['id_self']);
		}
		elseif (isset($params['history'])) {
			$conditions = sprintf('logs.type IN (%d, %d, %d) AND json_extract(logs.details, \'$.entity\') = \'Users\\User\' AND json_extract(logs.details, \'$.id\') = %d', self::CREATE, self::EDIT, self::DELETE, (int)$params['history']);
		}
		else {
			$conditions = '1';
		}

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('created', true);
		$list->setCount('COUNT(logs.id)');
		$list->setModifier(function (&$row) {
			//$row->created = \DateTime::createFromFormat('!Y-m-d H:i:s', $row->created);
			$row->details = $row->details ? json_decode($row->details) : null;
			$row->type_label = $row->type == self::MESSAGE ? ($row->details->message ?? '') : self::ACTIONS[$row->type];

			if (isset($row->details->entity)) {
				$const = 'Paheko\Entities\\' . $row->details->entity . '::NAME';

				if (defined($const)
					&& ($value = constant($const))) {
					$row->entity_name = $value;
				}

				$const = 'Paheko\Entities\\' . $row->details->entity . '::PRIVATE_URL';

				if (isset($row->details->id, $row->details->entity)
					&& $row->type !== self::DELETE
					&& defined($const)
					&& ($value = constant($const))) {
					$row->entity_url = sprintf($value, $row->details->id);
				}
			}
		});

		return $list;
	}
}
