<?php

namespace Garradin\Files;

use Garradin\Static_Cache;
use Garradin\DB;
use Garradin\Membres\Session;
use Garradin\Entities\Files\File;
use KD2\DB\EntityManager as EM;

class Folders
{
	const TEMPLATES = 'skel';
	const USERS = 'users';
	const TRANSACTIONS = 'accounting';
	const WEB = 'web';
	const CONFIG = 'config';

	static public function getFolderClause(bool $system, string $folder, ?string $subfolder = null)
	{
		$db = DB::getInstance();
		$where = sprintf('SELECT id FROM files_folders WHERE system = %d AND name = %s', $system, $db->quote($folder));

		if (null !== $subfolder) {
			$where = sprintf('SELECT id FROM files_folders WHERE system = %d AND name = %s AND parent = (%s)', $system, $db->quote($subfolder), $where);
		}

		return $where;
	}
}

