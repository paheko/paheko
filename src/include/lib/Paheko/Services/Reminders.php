<?php

namespace Paheko\Services;

use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Users\DynamicFields;
use Paheko\Entities\Services\Reminder;
use Paheko\Entities\Services\ReminderMessage;
use KD2\DB\EntityManager;
use stdClass;

use const Paheko\WWW_URL;
use const Paheko\ADMIN_URL;

class Reminders
{
	static public function getList(): DynamicList
	{
		$columns = [
			'service_label' => [
				'select' => 's.label',
				'label' => 'Activité',
				'order' => 's.label COLLATE U_NOCASE %s',
			],
			'delay' => [
				'select' => 'sr.delay',
				'label' => 'Délai de rappel',
			],
			'subject' => [
				'select' => 'sr.subject',
				'label' => 'Sujet',
				'order' => 'sr.subject COLLATE U_NOCASE %s',
			],
			'id' => ['select' => 'sr.id'],
		];

		$tables = 'services_reminders sr INNER JOIN services s ON s.id = sr.id_service';
		$list = new DynamicList($columns, $tables);
		$list->orderBy('service_label', false);
		return $list;
	}

	static public function get(int $id)
	{
		return EntityManager::findOneById(Reminder::class, $id);
	}

	static public function listSentForUser(int $user_id)
	{
		$columns = [
			'label' => [
				'label' => 'Activité',
				'select' => 's.label',
			],
			'delay' => [
				'label' => 'Délai du rappel',
				'select' => 'r.delay',
			],
			'date' => [
				'label' => 'Date d\'envoi du message',
				'select' => 'srs.sent_date',
			],
		];

		$tables = 'services_reminders_sent srs
			LEFT JOIN services_reminders r ON r.id = srs.id_reminder
			INNER JOIN services s ON s.id = srs.id_service';
		$conditions = sprintf('srs.id_user = %d', $user_id);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('date', true);
		return $list;
	}

	static public function listSentForReminder(int $reminder_id)
	{
		return DB::getInstance()->get('SELECT srs.sent_date, r.delay, s.label, rs.id AS sent_id, s.id AS service_id
			FROM services_reminders_sent srs
			INNER JOIN services_reminders r ON r.id = srs.id_reminder
			INNER JOIN services s ON s.id = srs.id_service
			WHERE rs.id_reminder = ?;', $reminder_id);
	}

	static public function listForService(int $service_id)
	{
		return DB::getInstance()->get('SELECT * FROM services_reminders WHERE id_service = ? ORDER BY delay, subject;', $service_id);
	}

	static public function getPendingSQL(bool $due_only = true, string $conditions = '1')
	{
		$db = DB::getInstance();

		$sql = 'SELECT
			u.*, %s AS identity,
			u.id AS id_user,
			date(sub.expiry_date, sr.delay || \' days\') AS reminder_date,
			ABS(julianday(date()) - julianday(sub.expiry_date)) AS nb_days,
			MAX(sr.delay) AS delay, sr.subject, sr.body, s.label, s.description,
			sub.expiry_date, sr.id AS id_reminder, sub.id_service, sub.id_user,
			sf.label AS fee_label, sf.amount, sf.formula
			FROM services_reminders sr
			INNER JOIN services s ON s.id = sr.id_service AND s.archived = 0
			-- Select latest subscription to a service (MAX) only
			INNER JOIN (SELECT MAX(sub2.expiry_date) AS expiry_date, sub2.id_user, sub2.id_service, sub2.id_fee FROM services_subscriptions AS sub2 GROUP BY id_user, id_service) AS sub ON s.id = sub.id_service
			-- Select fee
			LEFT JOIN services_fees sf ON sf.id = sub.id_fee
			-- Join with users, but not ones part of a hidden category
			INNER JOIN users u ON sub.id_user = u.id
				AND (%s)
				AND (u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1))
			-- Join with sent reminders to exclude users that already have received this reminder
			LEFT JOIN (SELECT id, MAX(due_date) AS due_date, id_user, id_reminder FROM services_reminders_sent GROUP BY id_user, id_reminder) AS srs ON sub.id_user = srs.id_user AND srs.id_reminder = sr.id
			WHERE
				(sr.not_before_date IS NULL OR sr.not_before_date <= date(sub.expiry_date, sr.delay || \' days\'))
				AND (srs.id IS NULL OR srs.due_date < date(sub.expiry_date, (sr.delay - 1) || \' days\'))
				AND %s
				AND %s
			GROUP BY sub.id_user, sr.id_service
			ORDER BY sub.id_user';

		$emails = DynamicFields::getEmailFields();
		$emails = array_map(fn($e) => sprintf('u.%s IS NOT NULL', $db->quoteIdentifier($e)), $emails);
		$emails = implode(' OR ', $emails);

		$sql = sprintf($sql,
			DynamicFields::getNameFieldsSQL('u'),
			$emails,
			$due_only ? 'date() > date(sub.expiry_date, sr.delay || \' days\')' : '1',
			$conditions
		);

		return $sql;
	}

	static public function createMessage(stdClass $reminder): ReminderMessage
	{
		$m = new ReminderMessage;
		$m->import([
			'id_service'  => $reminder->id_service,
			'id_user'     => $reminder->id_user,
			'id_reminder' => $reminder->id_reminder,
			'due_date'    => $reminder->reminder_date,
		]);

		return $m;
	}

	/**
	 * Envoi des rappels automatiques par e-mail
	 * @return boolean TRUE en cas de succès
	 */
	static public function sendPending(): void
	{
		$db = DB::getInstance();
		$sql = self::getPendingSQL(true);

		$date = new \DateTime;

		$db->begin();

		foreach ($db->iterate($sql) as $row) {
			$m = self::createMessage($row);
			$body = $m->getBody($row);

			$m->set('sent_date', $date);
			$m->send($row, $body);
		}

		$db->commit();
	}
}
