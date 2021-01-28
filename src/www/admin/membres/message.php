<?php
namespace Garradin;

use Garradin\Users\Categories;

require_once __DIR__ . '/_inc.php';

if (empty($user->email))
{
    throw new UserException("Vous devez renseigner l'adresse e-mail dans vos informations pour pouvoir contacter les autres membres.");
}

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$membre = $membres->get($id);

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

if (empty($membre->email))
{
    throw new UserException('Ce membre n\'a pas d\'adresse email renseignée.');
}

if (f('save'))
{
    $form->check('send_message_' . $id, [
        'sujet' => 'required|string',
        'message' => 'required|string',
    ]);

    if (!$form->hasErrors())
    {
        try {
            $session->sendMessage($membre->email, f('sujet'),
                f('message'), (bool) f('copie'));

            Utils::redirect(ADMIN_URL . 'membres/?sent');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('categorie', Categories::get($membre->category_id));
$tpl->assign('membre', $membre);

$tpl->display('admin/membres/message.tpl');
