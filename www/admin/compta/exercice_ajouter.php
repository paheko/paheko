<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_exercices.php';

$e = new Garradin_Compta_Exercices;

$error = false;

if (!empty($_POST['add']))
{
    if (!utils::CSRF_check('compta_ajout_exercice'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $e->add(array(
                'libelle'   =>  utils::post('libelle'),
                'debut'     =>  utils::post('debut'),
                'fin'       =>  utils::post('fin'),
            ));

            utils::redirect('/admin/compta/exercices.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('custom_js', array('datepickr.js'));

$tpl->display('admin/compta/exercice_ajouter.tpl');

?>