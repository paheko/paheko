<?php
declare(strict_types=1);

namespace Paheko\Files;

use Paheko\Entities\Files\File;
use Paheko\Entities\Files\Share;

use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Utils;
use Paheko\Users\Session;
use Paheko\Users\DynamicFields;

use KD2\DB\EntityManager as EM;

use DateTime;

class Shares
{
	static public function prune(): void
	{
		DB::getInstance()->preparedQuery('DELETE FROM files_shares WHERE expiry IS NOT NULL AND expiry < ?;', new DateTime);
	}

	static public function get(int $id): ?Share
	{
		return EM::findOneById(Share::class, $id);
	}

	static public function getByHashID(string $hash_id): ?Share
	{
		return EM::findOne(Share::class, 'SELECT * FROM @TABLE WHERE hash_id = ? AND (expiry IS NULL OR expiry > ?) LIMIT 1;', $hash_id, new DateTime);
	}

	static public function create(File $file, ?Session $session, int $option, int $ttl, ?string $password = null): Share
	{
		$share = new Share;
		$share->set('hash_id', Utils::random_string(12));
		$share->set('id_user', $session ? $session::getUserId() : null);
		$share->set('id_file', $file->id());
		$share->set('created', new DateTime);

		$share->importForm(compact('option', 'ttl', 'password'));

		return $share;
	}

	static protected function getListedColumns(bool $all)
	{
		$columns = [
			'id' => ['select' => 's.id'],
			'hash_id' => ['select' => 's.hash_id'],
			'file_name' => [
				'label' => 'Nom du fichier',
				'select' => 'f.name',
			],
			'file_parent' => [
				'label' => 'Chemin',
				'select' => 'f.parent',
			],
			'id_user' => [],
			'user_name' => [
				'label' => 'Lien créé par',
				'select' => DynamicFields::getNameFieldsSQL('u'),
			],
			'option' => [
				'label' => 'Autorisé à',
				'select' => 's.option',
			],
			'expiry' => [
				'label' => 'Expiration',
				'select' => 's.expiry',
			],
			'password' => [
				'label' => 'Mot de passe',
				'select' => 'CASE WHEN s.password IS NOT NULL THEN 1 ELSE 0 END',
			],
			'created' => [
				'label' => 'Création',
				'select' => 's.created',
			],
		];

		if (!$all) {
			unset($columns['file_name'], $columns['file_parent']);
		}

		return $columns;
	}

	static public function getList(): DynamicList
	{
		$tables = Share::TABLE . ' AS s
			INNER JOIN files AS f ON f.id = s.id_file
			LEFT JOIN users AS u ON u.id = s.id_user';

		$list = new DynamicList(self::getListedColumns(true), $tables);
		$list->orderBy('created', true);
		$list->setPageSize(null);
		return $list;
	}

	static public function getListForFile(File $file): DynamicList
	{
		$list = self::getList();
		$list->setColumns(self::getListedColumns(false));
		$list->setConditions('id_file = ' . (int)$file->id());
		return $list;
	}
}
