<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$e = new Compta\Exercices;

$error = false;

if (!empty($_POST['add']))
{
    if (!Utils::CSRF_check('compta_ajout_exercice'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $e->add([
                'libelle'   =>  Utils::post('libelle'),
                'debut'     =>  Utils::post('debut'),
                'fin'       =>  Utils::post('fin'),
            ]);

            Utils::redirect('/admin/compta/exercices/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->display('admin/compta/exercices/ajouter.tpl');

?>