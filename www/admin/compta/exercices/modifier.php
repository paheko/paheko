<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_exercices.php';
$e = new Garradin_Compta_Exercices;

$exercice = $e->get((int)utils::get('id'));

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

if ($exercice['cloture'])
{
    throw new UserException('Impossible de modifier un exercice clôturé.');
}

$error = false;

if (!empty($_POST['edit']))
{
    if (!utils::CSRF_check('compta_modif_exercice_'.$exercice['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $e->edit($exercice['id'], array(
                'libelle'   =>  utils::post('libelle'),
                'debut'     =>  utils::post('debut'),
                'fin'       =>  utils::post('fin'),
            ));

            utils::redirect('/admin/compta/exercices/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('exercice', $exercice);
$tpl->assign('custom_js', array('datepickr.js'));

$tpl->display('admin/compta/exercices/modifier.tpl');

?>