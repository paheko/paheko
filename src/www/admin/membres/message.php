<?php
namespace Garradin;

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

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('send_message_'.$id))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (!Utils::post('sujet'))
    {
        $error = 'Le sujet ne peut rester vide.';
    }
    elseif (!Utils::post('message'))
    {
        $error = 'Le message ne peut rester vide.';
    }
    else
    {
        try {
            $membres->sendMessage($membre->email, Utils::post('sujet'),
                Utils::post('message'), (bool) Utils::post('copie'));

            Utils::redirect('/admin/membres/?sent');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$cats = new Membres\Categories;

$tpl->assign('categorie', $cats->get($membre->id_categorie));
$tpl->assign('membre', $membre);
$tpl->assign('error', $error);

$tpl->display('admin/membres/message.tpl');
