<?php

namespace Paheko\Email;

use Paheko\Entities\Email\Mailing;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Users\DynamicFields;
use Paheko\Search;
use Paheko\Entities\Search as SearchEntity;
use Paheko\Users\Categories;
use Paheko\UserException;
use Paheko\Services\Services;

use KD2\DB\EntityManager;

class Mailings
{
	static public function getList(): DynamicList
	{
		$columns = [
			'id' => [
			],
			'subject' => [
				'label' => 'Sujet',
			],
			'nb_recipients' => [
				'label' => 'Destinataires',
				'select' => '(SELECT COUNT(*) FROM mailings_recipients WHERE id_mailing = mailings.id)',
			],
			'sent' => [
				'label' => 'Date d\'envoi',
				'order' => 'id %s',
			],
		];

		$tables = 'mailings';

		$list = new DynamicList($columns, $tables);
		$list->orderBy('sent', true);
		return $list;
	}

	static public function get(int $id): ?Mailing
	{
		return EntityManager::findOneById(Mailing::class, $id);
	}

	static public function create(string $subject, string $target_type, ?string $target_value, ?string $target_label): Mailing
	{
		$db = DB::getInstance();
		$db->begin();

		$m = new Mailing;
		$m->set('subject', $subject);
		$m->importForm(compact('subject', 'target_type', 'target_value', 'target_label'));
		$m->save();
		$m->populate();

		$db->commit();
		return $m;
	}

	static public function anonymize(): void
	{
		$em = EntityManager::getInstance(Mailing::class);
		$db = DB::getInstance();

		$db->begin();
		foreach ($em->iterate('SELECT * FROM @TABLE WHERE sent < datetime(\'now\', \'-6 month\') AND anonymous = 0;') as $m) {
			$m->anonymize();
			$m->set('anonymous', true);
			$m->save();
		}

		$db->commit();
	}

	static public function listTargets(string $type): array
	{
		if ($type === 'field') {
			$list = self::listCheckboxFieldsTargets();
		}
		elseif ($type === 'category') {
			$list = Categories::listWithStats(Categories::WITHOUT_HIDDEN);
		}
		elseif ($type === 'service') {
			$list = iterator_to_array(Services::listWithStats(true)->iterate());
		}
		elseif ($type === 'search') {
			$list = Search::list(SearchEntity::TARGET_USERS, Session::getUserId());
			$list = array_filter($list, fn($s) => $s->hasUserId());
			array_walk($search_list, function (&$s) {
				$s = (object) ['label' => $s->label, 'id' => $s->id, 'count' => $s->countResults()];
			});

		}
		else {
			throw new \InvalidArgumentException('Unknown target type: ' . $type);
		}

		if (!count($list)) {
			throw new UserException('Il n\'y aucun résultat correspondant à cette cible d\'envoi.');
		}

		return $list;
	}

	static public function listCheckboxFieldsTargets(): array
	{
		 $fields = DynamicFields::getInstance()->fieldsByType('checkbox');

		 if (!count($fields)) {
		 	return [];
		 }

		 $db = DB::getInstance();
		 $sql = [];

		 foreach ($fields as $field) {
		 	$sql[] = sprintf('SELECT %s AS name, %s AS label, COUNT(*) AS count FROM users WHERE %s = 1 AND id_category IN (SELECT id FROM users_categories WHERE hidden = 0)',
		 		$db->quote($field->name),
		 		$db->quote($field->label),
		 		$db->quoteIdentifier($field->name)
		 	);
		 }

		 $sql = implode(' UNION ALL ', $sql);
		 return $db->get($sql);
	}

	static public function getOptoutUsersList(): DynamicList
	{
		$db = DB::getInstance();
		$email_field = 'u.' . $db->quoteIdentifier(DynamicFields::getFirstEmailField());

		$columns = [
			'id' => [
				'select' => 'e.id',
			],
			'identity' => [
				'label' => 'Membre',
				'select' => DynamicFields::getNameFieldsSQL('u'),
			],
			'email' => [
				'label' => 'Adresse',
				'select' => $email_field,
			],
			'user_id' => [
				'select' => 'u.id',
			],
			'hash' => [
			],
			'status' => [
				'label' => 'Désinscription',
				'select' => 'CASE WHEN e.optout = 1 THEN \'Désinscription globale\' ELSE o.target_label END',
			],
			'sent_count' => [
				'label' => 'Messages envoyés',
			],
			'last_sent' => [
				'label' => 'Dernière tentative d\'envoi',
			],
			'optout' => [],
			'target_type' => [],
			'target_label' => [],
		];

		$tables = sprintf('users u
			INNER JOIN emails e ON e.hash = email_hash(%1$s)
			LEFT JOIN mailings_optouts o ON o.email_hash = e.hash', $email_field);

		$conditions = sprintf('%s IS NOT NULL AND %1$s != \'\' AND (e.optout = 1 OR o.email_hash IS NOT NULL)', $email_field);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('last_sent', true);
		$list->setModifier(function (&$row) {
			$row->last_sent = $row->last_sent ? new \DateTime($row->last_sent) : null;
		});
		return $list;
	}

}
