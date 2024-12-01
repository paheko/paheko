<?php

namespace Paheko\Email;

use Paheko\Entities\Email\Mailing;
use Paheko\DB;
use Paheko\DynamicList;

use KD2\DB\EntityManager;

class Mailings
{
	static public function getList(): DynamicList
	{
		$columns = [
			'id' => [
				'select' => 'm.id',
			],
			'subject' => [
				'label' => 'Sujet',
				'select' => 'm.subject',
			],
			'nb_recipients' => [
				'label' => 'Destinataires',
				'select' => 'COUNT(r.id)',
			],
			'sent' => [
				'label' => 'Date d\'envoi',
				'order' => 'm.id %s',
			],
		];

		$tables = 'mailings m LEFT JOIN mailings_recipients r ON r.id_mailing = m.id';

		$list = new DynamicList($columns, $tables);
		$list->groupBy('m.id');
		$list->orderBy('sent', true);
		return $list;
	}

	static public function get(int $id): ?Mailing
	{
		return EntityManager::findOneById(Mailing::class, $id);
	}

	static public function create(string $subject, string $target, ?string $target_id): Mailing
	{
		$db = DB::getInstance();
		$db->begin();

		$m = new Mailing;
		$m->set('subject', $subject);
		$m->save();
		$m->populate($target, $target_id);

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
}
