<?php
namespace Paheko;

use Paheko\Email\Mailings;

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
	$tpl->display('users/mailing/preview.tpl');
}
elseif ($view === 'text') {
	$text = $mailing->getTextPreview(null, false);
	$text = htmlspecialchars($text);
	$text = Utils::linkifyURLs($text);
	$tpl->assign('code', $text);
	$tpl->display('users/mailing/preview.tpl');
}
else {
	$text = $mailing->getHTMLPreview((int)qg('preview') ?: null, true);

	if ($view === 'handheld') {
		$text = preg_replace_callback('/<body[^>]*style="[^"]*"/', function ($match) {
			return trim(substr($match[0], 0, -1), "\t\r\n; ") . '; max-width: 360px; margin: 0 auto; background: #fff; box-shadow: 0px 0px 5px #999" class="pko-preview"';
		}, $text);

		$text = preg_replace('/<html[^>]*/', '$0 style="background: rgba(255, 255, 255, 0.7);"', $text);
	}

	echo $text;
}
