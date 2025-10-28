<?php
namespace Paheko;

const SKIP_STARTUP_CHECK = true;

require_once __DIR__ . '/../../include/init.php';

if (!DESKTOP_CONFIG_FILE) {
	throw new UserException('Cette page est désactivée.');
}

$tpl = Template::getInstance();
$tpl->assign('admin_url', ADMIN_URL);

$path = $_GET['path'] ?? dirname(DB_FILE);
$csrf_key = 'create_db';

if (!is_writable($path)) {
	throw new UserException('Ce chemin n\'est pas accessible en écriture (problème de permissions ?)');
}

$form = new Form;
$form->runIf('create', function () use ($path) {
	$path = rtrim($path, '/') . '/' . ($_POST['name'] ?? 'association') . '.sqlite';

	if (file_exists($path)) {
		throw new UserException('Ce fichier existe déjà');
	}

	Install::setConfig(DESKTOP_CONFIG_FILE, ['DB_FILE' => $path]);
	Utils::redirect('!');
}, $csrf_key, '!');

$tpl->assign(compact('csrf_key', 'form'));

$tpl->display('create_db.tpl');
