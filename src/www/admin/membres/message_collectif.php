<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\Emails;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$recherche = new Recherche;
$csrf_key = 'send_mailing';

$form->runIf(f('send') || f('subject'), function () use ($membres, &$mailing, $recherche) {
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
		$recipients = $membres->listAllButHidden();
	}
	elseif ($target[0] == 'category') {
		$recipients = $membres->listAllByCategory($target[1], true);
	}
	elseif ($target[0] == 'search') {
		$recipients = $recherche->search($target[1], ['membres.*'], true);
	}

	if (empty($recipients)) {
		throw new UserException('La liste de destinataires sélectionnée ne comporte aucun membre, ou aucun avec une adresse e-mail renseignée.');
	}

	$mailing = Emails::createMailing($recipients, f('subject'), f('message'), (bool) f('send_copy'), f('render') ?: null);
}, $csrf_key);

$form->runIf('send', function () use ($membres, $mailing) {
	Emails::sendMailing($mailing);
}, $csrf_key, '!membres/message_collectif.php?sent');

$tpl->assign('categories', Categories::listNotHidden());
$tpl->assign('preview', f('preview') && $mailing ? $mailing->preview : null);
$tpl->assign('recipients_count', $mailing ? count($mailing->recipients) : 0);
$tpl->assign('search_list', $recherche->getList($user->id, 'membres'));

$tpl->assign('render_formats', Emails::RENDER_FORMATS);

$tpl->assign(compact('csrf_key'));

$tpl->assign('sent', null !== qg('sent'));

$tpl->display('admin/membres/message_collectif.tpl');
