<?php
namespace Paheko;

use Paheko\Email\Mailings;
use KD2\Brindille_Exception;

require_once __DIR__ . '/_inc.php';

$mailing = Mailings::get((int)qg('id'));

if (!$mailing) {
	throw new UserException('Unknown mailing ID');
}

$view = $_GET['view'] ?? 'desktop';

$tpl->assign(compact('view'));

if ($view === 'code') {
	$text = $mailing->body;
	$text = htmlspecialchars($text);
	$tpl->assign('code', $text);
	$tpl->display('email/mailing/preview.tpl');
}
elseif ($view === 'text') {
	try {
		$text = $mailing->getTextPreview(null, false);
	}
	catch (Brindille_Exception $e) {
		$text = "/!\ Erreur dans le code du message !!!\n" . $e->getMessage();
	}

	$text = htmlspecialchars($text);
	$text = Utils::linkifyURLs($text);
	$tpl->assign('code', $text);
	$tpl->display('email/mailing/preview.tpl');
}
else {
	try {
		$text = $mailing->getHTMLPreview((int)qg('preview') ?: null, true);
	}
	catch (\KD2\Brindille_Exception $e) {
		$text = sprintf('<div style="margin: 10px auto; background: #fcc; padding: 10px; max-width: 600px; font-size: 1.2em"><h2>Erreur dans le code du message</h2>%s</div>', htmlspecialchars($e->getMessage()));
	}

	if ($view === 'handheld') {
		$text = preg_replace_callback('/<body[^>]*style="[^"]*"/', function ($match) {
			return trim(substr($match[0], 0, -1), "\t\r\n; ") . '; max-width: 360px; margin: 0 auto; background: #fff; box-shadow: 0px 0px 5px #999" class="pko-preview"';
		}, $text);

		$text = preg_replace('/<html[^>]*/', '$0 style="background: rgba(255, 255, 255, 0.7);"', $text);
	}

	echo $text;
}
