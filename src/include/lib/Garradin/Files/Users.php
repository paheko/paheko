<?php

namespace Garradin\Files;

use Garradin\Entities\Files\File;
use Garradin\Entities\Users\User;
use Garradin\DynamicList;
use Garradin\Config;

class Users
{
	const LIST_COLUMNS = [
		'number' => [
			'select' => 'm.numero',
			'label' => 'Numéro',
		],
		'identity' => [
			'select' => '',
			'label' => '',
		],
		'path' => [
		],
		'id' => [
			'label' => null,
			'select' => 'm.id',
		],
	];

	static public function list()
	{
		Files::syncVirtualTable(File::CONTEXT_USER);

		$config = Config::getInstance();
		$name_field = $config->get('champ_identite');
		$champs = $config->get('champs_membres');

		$columns = self::LIST_COLUMNS;
		$columns['identity']['select'] = 'm.' . $name_field;
		$columns['identity']['label'] = $champs->get($name_field)->title;

		$tables = sprintf('%s f INNER JOIN membres m ON m.id = f.name', Files::getVirtualTableName());

		$sum = 0;

		// Only fetch directories with an ID as the name
		$conditions = sprintf('f.parent = \'%s\' AND f.type = %d AND printf("%%d", f.name) = name', File::CONTEXT_USER, File::TYPE_DIRECTORY);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('number', false);
		$list->setCount('COUNT(DISTINCT m.id)');

		return $list;
	}

	static public function deleteMultiple(array $ids): void
	{
		$session = Session::getInstance();

		if ($session->isLogged()) {
			$user = $session->getUser();

			foreach ($ids as $id) {
				if ($user->id == $id) {
					throw new UserException('Il n\'est pas possible de supprimer son propre compte.');
				}
			}
		}

		foreach ($ids as &$id)
		{
			$id = (int) $id;
			Files::delete(File::CONTEXT_USER . '/' . $id);
		}

		$db = DB::getInstance();

		// Suppression du membre
		$db->delete(User::TABLE, $db->where('id', $membres));
	}

    static public function changeCategory(int $category_id, array $ids)
    {
		$session = Session::getInstance();
		$user_id = null;

		if ($session->isLogged()) {
			$user_id = $session->getUser()->id;
		}

        foreach ($ids as &$id) {
            $id = (int) $id;

            // Don't allow current user ID to change his/her category
            // as that means he/she could be logged out
            if ($id == $user_id) {
            	$id = null;
            }
        }

        unset($id);

        // Remove logged-in user ID
        $ids = array_filter($ids);

        $db = DB::getInstance();
        return $db->update(User::TABLE,
            ['id_category' => $category_id],
            $db->where('id', $ids)
        );
    }
}