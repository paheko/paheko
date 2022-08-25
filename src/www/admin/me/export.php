<?php
namespace Garradin;

use Garradin\Users\Session;
use Garradin\Services\Services_User;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;

use KD2\ZipWriter;

require_once __DIR__ . '/_inc.php';

$services_list = Services_User::perUserList($user->id);
$services_list->setPageSize(null);

$export_data = [
	'user' => $user,
	'services' => $services_list->asArray(true),
];

$tpl->assign(compact('user', 'services_list'));

$name = sprintf('%s - Donnees - %s.zip', Config::getInstance()->get('org_name'), $user->name());
header('Content-type: application/zip');
header(sprintf('Content-Disposition: attachment; filename="%s"', $name));

$zip = new ZipWriter('php://output');
$zip->setCompression(0);


$zip->add('info.html', $tpl->fetch('me/export.tpl'));
$zip->add('info.json', json_encode($export_data));

foreach ($user->listFiles() as $file) {
	$zip->add($file->path, null, $file->fullpath());
}

$zip->close();