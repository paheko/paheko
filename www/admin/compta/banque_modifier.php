<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_comptes_bancaires.php';
$banque = new Garradin_Compta_Comptes_Bancaires;

$compte = $banque->get(utils::get('id'));

if (!$compte)
{
    throw new UserException('Le compte demandé n\'existe pas.');
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('compta_edit_banque_'.$compte['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $banque->edit($compte['id'], array(
                'libelle'       =>  utils::post('libelle'),
                'banque'        =>  utils::post('banque'),
                'iban'          =>  utils::post('iban'),
                'bic'           =>  utils::post('bic'),
            ));

            utils::redirect('/admin/compta/banques.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('compte', $compte);

$tpl->display('admin/compta/banque_modifier.tpl');

?>