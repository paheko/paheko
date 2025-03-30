<?php
namespace Paheko;

const SKIP_STARTUP_CHECK = true;
//const LOGIN_PROCESS = true;

require_once __DIR__ . '/../../include/init.php';

if (!DESKTOP_CONFIG_FILE) {
	throw new UserException('Cette page est désactivée.');
}

$tpl = Template::getInstance();
$tpl->assign('admin_url', ADMIN_URL);

$path = $_GET['path'] ?? dirname(DB_FILE);

$list = [];
$error = null;

try {
	if (!is_readable($path)) {
		throw new UserException('Ce chemin n\'est pas accessible (problème de permissions ?)');
	}
	elseif (is_dir($path)) {
		$dir = dir($path);

		while ($file = $dir->read()) {
			if ($file[0] === '.') {
				continue;
			}

			$uri = rawurlencode(rtrim($path, '/') . '/' . $file);

			if (is_dir($path . '/' . $file)) {
				$list['0' . $file] = ['dir' => true, 'name' => $file, 'uri' => $uri];
			}
			elseif (preg_match('/\.sqlite$/', $file)) {
				$list['1' . $file] = ['dir' => false, 'name' => $file, 'uri' => $uri];
			}
		}

		uksort($list, 'strnatcasecmp');
	}
	elseif (is_file($path)) {
		$details = Backup::getDBDetails($path);

		if (!empty($details->error)) {
			throw new UserException('Cette base de données n\'est pas utilisable : ' . $details->error);
		}
		elseif (empty($details->can_restore)) {
			throw new UserException(sprintf('Cette base de données est trop ancienne (version %s)', $details->version));
		}

		Install::setConfig(DESKTOP_CONFIG_FILE, ['DB_FILE' => $path]);
		Utils::redirect('!');
	}
}
catch (UserException $e) {
	$error = $e->getMessage();
}

$parent = realpath(Utils::dirname($path));
$parent_uri = rawurlencode($parent);

$tpl->assign(compact('path', 'list', 'parent_uri', 'error'));
$tpl->assign('current_db', DB_FILE);

$tpl->display('open_db.tpl');
