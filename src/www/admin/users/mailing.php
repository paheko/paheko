<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\Session;
use Garradin\Users\Users;
use Garradin\Search;
use Garradin\Services\Services;
use Garradin\Entities\Search as SearchEntity;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'send_mailing';

$form->runIf('send', function () {
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

	if ($target[0] == 'all') {
		$recipients = Users::getEmailsByCategory(null);
	}
	elseif ($target[0] == 'category') {
		$recipients = Users::getEmailsByCategory((int) $target[1]);
	}
	elseif ($target[0] == 'service') {
		$recipients = Users::getEmailsByService((int) $target[1]);
	}
	elseif ($target[0] == 'search') {
		$recipients = Users::getEmailsBySearch((int) $target[1]);
	}

	if (!count($recipients)) {
		throw new UserException('La liste de destinataires sélectionnée ne comporte aucun membre, ou aucun avec une adresse e-mail renseignée.');
	}

	Users::sendMessage($recipients, f('subject'), f('message'), (bool) f('copy'));
}, $csrf_key, '!users/mailing.php?sent');

$tpl->assign('categories', Categories::listNotHidden());
$tpl->assign('services', Services::listAssoc());
$tpl->assign('search_list', Search::list(Session::getUserId(), SearchEntity::TARGET_USERS));
$tpl->assign(compact('csrf_key'));

$tpl->assign('sent', null !== qg('sent'));



$tpl->display('users/mailing.tpl');
