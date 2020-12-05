<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$cats = new Membres\Categories;
$recherche = new Recherche;

if (f('send'))
{
    $form->check('send_message_co', [
        'sujet'      => 'required|string',
        'message'    => 'required|string',
        'recipients' => 'required|string',
    ]);

    if (preg_match('/^(categorie|recherche)_(\d+)$/', f('recipients'), $match))
    {
        if ($match[1] == 'categorie')
        {
            $recipients = $membres->listAllByCategory($match[2], true);
        }
        else
        {
            try {
                $recipients = $recherche->search($match[2], ['id', 'email'], true);
            }
            catch (UserException $e) {
                $form->addError($e->getMessage());
            }
        }

        if (isset($recipients) && !count($recipients))
        {
            $form->addError('La liste de destinataires sélectionnée ne comporte aucun membre, ou aucun avec une adresse e-mail renseignée.');
        }
    }
    else
    {
        $form->addErrror('Destinataires invalides : ' . f('recipients'));
    }

    if (!$form->hasErrors())
    {
        try {
            $membres->sendMessage($recipients, f('sujet'),
                f('message'), (bool) f('copie'));

            Utils::redirect(ADMIN_URL . 'membres/?sent');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('categories', $cats->listNotHidden());
$tpl->assign('recherches', $recherche->getList($user->id, 'membres'));

$tpl->display('admin/membres/message_collectif.tpl');
