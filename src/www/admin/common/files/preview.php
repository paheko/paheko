<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Users\Session;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Ce fichier est introuvable.');
}

if (!$file->canRead()) {
	throw new UserException('Vous n\'avez pas le droit de lire ce fichier.');
}

$file->preview(Session::getInstance());
