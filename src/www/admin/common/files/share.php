<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Fichier inconnu');
}

if (!$file->canWrite()) {
	throw new UserException('Vous n\'avez pas le droit de partager ce fichier.');
}

$context = $file->context();

$csrf_key = 'file_share_' . $file->pathHash();
$share_url = null;

$form->runIf('share', function () use ($file, &$share_url) {
	$share_url = $file->createShareLink(f('expiry'), f('password'));
}, $csrf_key);

$expiry_options = [
	1         => 'Une heure',
	24        => 'Un jour',
	24*31     => 'Un mois',
	24*365    => 'Un an',
	24*365*10 => 'Dix ans',
	24*365*30 => 'Infinie',
];

$tpl->assign(compact('file', 'csrf_key', 'share_url', 'expiry_options'));

$tpl->display('common/files/share.tpl');