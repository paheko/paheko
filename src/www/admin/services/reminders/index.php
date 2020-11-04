<?php
namespace Garradin;

use Garradin\Entities\Services\Reminder;
use Garradin\Services\Reminders;
use Garradin\Services\Services;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$csrf_key = 'reminder_add';

$form->runIf('save', function () {
    $reminder = new Reminder;
    $reminder->importForm();
    $reminder->save();
}, $csrf_key, Utils::getSelfURL());

$list = Reminders::list();
$services_list = Services::listAssoc();

$default_subject = '[#NOM_ASSO] Échéance de cotisation';
$default_body = "Bonjour #IDENTITE,\n\nVotre cotisation arrive à échéance dans #NB_JOURS jours.\n\n"
    .   "Merci de nous contacter pour renouveler votre cotisation.\n\nCordialement.\n\n"
    .   "--\n#NOM_ASSO\n#ADRESSE_ASSO\nE-Mail : #EMAIL_ASSO\nSite web : #SITE_ASSO";

$tpl->assign(compact('csrf_key', 'list', 'services_list', 'default_subject', 'default_body'));

$tpl->display('services/reminders/index.tpl');
