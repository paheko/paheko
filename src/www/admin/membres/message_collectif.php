<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

if (f('save'))
{
    $form->check('send_message_collectif', [
        'sujet'      => 'required|string',
        'message'    => 'required|string',
        'dest'       => 'numeric',
        'subscribed' => 'boolean',
    ]);

    if (!$form->hasErrors())
    {
        try {
            $membres->sendMessageToCategory(f('dest'), f('sujet'), f('message'), (bool) f('subscribed'));
            Utils::redirect('/admin/membres/?sent');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$cats = new Membres\Categories;

$tpl->assign('cats_liste', $cats->listSimple());
$tpl->assign('cats_cachees', $cats->listHidden());

$tpl->display('admin/membres/message_collectif.tpl');
