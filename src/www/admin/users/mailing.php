<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\Session;
use Garradin\Users\Users;
use Garradin\Search;
use Garradin\Services\Services;
use Garradin\Entities\Search as SearchEntity;
use Garradin\Users\Emails;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$mailing = null;
$csrf_key = 'send_mailing';

$form->runIf(f('send') || f('subject'), function () use (&$mailing) {
	if (!trim(f('subject'))) {
		throw new UserException('Le sujet ne peut rester vide.');
	}

	if (!trim(f('message'))) {
		throw new UserException('Le message ne peut rester vide.');
	}

	if (!f('target')) {
		throw new UserException('Aucun destinataire sélectionné.');
	}

	$target = explode('_', f('target'));

	if (count($target) !== 2) {
		throw new UserException('Destinataire invalide');
	}

	$count = null;

	if ($target[0] == 'all') {
		$recipients = Users::iterateEmailsByCategory(null);
	}
	elseif ($target[0] == 'category') {
		$recipients = Users::iterateEmailsByCategory((int)$target[1]);
	}
	elseif ($target[0] == 'search') {
		$recipients = Users::iterateEmailsBySearch((int)$target[1]);
	}
	elseif ($target[0] == 'service') {
		$recipients = Users::iterateEmailsByActiveService((int)$target[1]);
	}

	if (empty($recipients)) {
		throw new UserException('La liste de destinataires sélectionnée ne comporte aucun membre, ou aucun avec une adresse e-mail renseignée.');
	}

	$mailing = Emails::createMailing($recipients, f('subject'), f('message'), (bool) f('send_copy'), f('render') ?: null);
}, $csrf_key);

$form->runIf('export', function() use ($mailing) {
	Emails::exportMailing(f('export'), $mailing);
	exit;
});

$form->runIf('send', function () use ($mailing) {
	Emails::sendMailing($mailing);
}, $csrf_key, '!users/mailing.php?sent');

$tpl->assign('categories', Categories::listNotHidden());
$tpl->assign('services', Services::listAssoc());
$tpl->assign('search_list', Search::list(Session::getUserId(), SearchEntity::TARGET_USERS));

$tpl->assign('preview', f('preview') && $mailing ? $mailing->preview : null);
$tpl->assign('recipients_count', $mailing ? count($mailing->recipients) : 0);

$tpl->assign('render_formats', Emails::RENDER_FORMATS);

$tpl->assign(compact('csrf_key'));

$tpl->assign('sent', null !== qg('sent'));

$tpl->display('users/mailing.tpl');
