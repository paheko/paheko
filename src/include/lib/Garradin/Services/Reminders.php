<?php

namespace Garradin\Services;

use Garradin\Config;
use Garradin\DB;
use Garradin\Entities\Services\Reminder;
use KD2\DB\EntityManager;

class Reminders
{
	static public function list()
	{
		return DB::getInstance()->get('SELECT s.label AS service_label, sr.* FROM services_reminders sr INNER JOIN services s ON s.id = sr.id_service
			ORDER BY s.label COLLATE NOCASE;');
	}

	static public function get(int $id)
	{
		return EntityManager::findOneById(Reminder::class, $id);
	}

	static public function listSentForUser(int $user_id)
	{
		return DB::getInstance()->get('SELECT rs.date AS sent_date, r.delay, s.label, rs.id AS sent_id, s.id AS service_id
			FROM services_reminders_sent rs
			INNER JOIN services_reminders r ON r.id = rs.id_reminder
			INNER JOIN services s ON s.id = rs.id_service
			WHERE rs.id_user = ?;', $user_id);
	}

	static public function listSentForReminder(int $reminder_id)
	{
		return DB::getInstance()->get('SELECT rs.date AS sent_date, r.delay, s.label, rs.id AS sent_id, s.id AS service_id
			FROM services_reminders_sent rs
			INNER JOIN services_reminders r ON r.id = rs.id_reminder
			INNER JOIN services s ON s.id = rs.id_service
			WHERE rs.id_reminder = ?;', $user_id);
	}

	static public function listForService(int $service_id)
	{
		return DB::getInstance()->get('SELECT * FROM services_reminders WHERE id_service = ? ORDER BY delay, subject;', $service_id);
	}

	/**
	 * Remplacer les tags dans le contenu/sujet du mail
	 * @param  string $content Chaîne à traiter
	 * @param  array  $data    Données supplémentaires à utiliser comme tags (tableau associatif)
	 * @return string          $content dont les tags ont été remplacés par le contenu correct
	 */
	static public function replaceTagsInContent(string $content, ?array $data = null)
	{
		$config = Config::getInstance();
		$tags = [
			'#NOM_ASSO'		=>	$config->get('nom_asso'),
			'#ADRESSE_ASSO'	=>	$config->get('adresse_asso'),
			'#EMAIL_ASSO'	=>	$config->get('email_asso'),
			'#SITE_ASSO'	=>	$config->get('site_asso'),
			'#URL_RACINE'	=>	WWW_URL,
			'#URL_SITE'		=>	WWW_URL,
			'#URL_ADMIN'	=>	ADMIN_URL,
		];

		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key=>$value)
			{
				$key = '#' . strtoupper($key);
				$tags[$key] = $value;
			}
		}

		return strtr($content, $tags);
	}

	/**
	 * Envoi de mail pour rappel automatisé
	 * @param  array $data Données du rappel automatisé
	 * @return boolean     TRUE
	 */
	static public function sendAuto($reminder)
	{
		$replace = (array) $data;
		$replace['date_rappel'] = Utils::sqliteDateToFrench($replace['date_rappel']);
		$replace['date_expiration'] = Utils::sqliteDateToFrench($replace['expiration']);
		$replace['nb_jours'] = abs($replace['nb_jours']);
		$replace['delai'] = abs($replace['delai']);

		$subject = self::replaceTagsInContent($data->sujet, $replace);
		$text = self::replaceTagsInContent($data->texte, $replace);

		// Envoi du mail
		Utils::sendEmail(Utils::EMAIL_CONTEXT_PRIVATE, $data->email, $subject, $text, $data->id);

		$db = DB::getInstance();
		$db->insert('services_reminders_sent', [
			'id_service'  => $reminder->id_service,
			'id_user'     => $reminder->id_user,
			'id_reminder' => $reminder->id_reminder,
			// On enregistre la date de mise en œuvre du rappel
			// et non pas la date d'envoi effective du rappel
			// car l'envoi du rappel peut ne pas être effectué
			// le jour où il aurait dû être envoyé (la magie des cron)
			'date'        => $reminder->sent_date,
		]);

		Plugin::fireSignal('rappels.auto', $reminder);

		return true;
	}

	/**
	 * Envoi des rappels automatiques par e-mail
	 * @return boolean TRUE en cas de succès
	 */
	static public function sendPending()
	{
		$db = DB::getInstance();
		$config = Config::getInstance();

		$sql = 'SELECT MIN(sr.delay), sr.subject, sr.body,
			s.label, s.description, su.id_service, su.id_user,
			m.email, m.%s AS identity, su.expiry_date
			FROM services_users su
			INNER JOIN services_reminders sr ON sr.id_service = su.id_service
			-- Join with users, but not ones part of a hidden category
			INNER JOIN membres m ON su.id_user = m.id
				AND m.email IS NOT NULL
				AND (m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1))
			-- Join with sent reminders to exclude users that already have received this reminder
			LEFT JOIN services_reminders_sent srs ON srs.id_reminder = sr.id AND srs.id_user = su.id_user
			WHERE date() > date(su.expiry_date, sr.delay || \' days\')
			AND srs.id IS NULL
			GROUP BY su.id_user, su.id_service
			ORDER BY su.id_user;';

		$sql = sprintf($sql, $config->get('champ_identite'));

		$db->begin();

		foreach ($db->iterate($sql) as $row)
		{
			self::sendAuto($row);
		}

		$db->commit();
		return true;
	}
}
