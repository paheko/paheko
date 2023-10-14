<?php
namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Users\Session;
use Paheko\Users\Users;

require_once __DIR__ . '/_inc.php';

$self = Session::getInstance()->user();

if (!$self->canEmail()) {
	throw new UserException('Vous devez renseigner une adresse e-mail dans votre fiche membre pour pouvoir envoyer des messages personnels.');
}

$user = Users::get((int) $_GET['id'] ?? 0);

if (!$user) {
	throw new UserException("Ce membre n'existe pas.");
}

if (!$user->canEmail()) {
	throw new UserException('Ce membre n\'a pas d\'adresse e-mail renseignée dans sa fiche membre.');
}

$csrf_key = 'send_message_' . $user->id;

$form->runIf('send', function () use ($user, $self) {
	$user->sendMessage(f('subject'), f('message'), (bool) f('send_copy'), $self);
}, $csrf_key, '!users/?sent');

$tpl->assign('category', Categories::get($user->id_category));
$tpl->assign('recipient', $user);
$tpl->assign(compact('self', 'csrf_key'));

$tpl->display('users/message.tpl');
