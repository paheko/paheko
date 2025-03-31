<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\UserTemplate\Modules;
use Paheko\Users\Session;

require __DIR__ . '/../../_inc.php';

$path = qg('p');
$file = Files::get($path);
$content = null;

if (!$file
	&& Files::getContext($path) == File::CONTEXT_MODULES
	&& File::canCreate($path)
	&& ($content = Modules::fetchDistFile($path))
	&& null !== $content) {
	$file = Files::createObject($path, Session::getInstance());
}
elseif (!$file) {
	throw new UserException('Fichier inconnu');
}

if (!$file->canWrite()) {
	throw new UserException('Vous n\'avez pas le droit de modifier ce fichier.');
}

// Handle all the file editor
$saved = $file->editor($content, Session::getInstance());

if ($saved && ($_SERVER['HTTP_ACCEPT'] ?? null) === 'application/json') {
	http_response_code(204);
	return;
}
elseif ($saved) {
	Utils::redirect(Utils::getSelfURI());
}
