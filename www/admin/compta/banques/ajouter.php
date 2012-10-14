<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_comptes_bancaires.php';
$banque = new Garradin_Compta_Comptes_Bancaires;

$error = false;

if (!empty($_POST['add']))
{
    if (!utils::CSRF_check('compta_ajout_banque'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $banque->add(array(
                'libelle'       =>  utils::post('libelle'),
                'banque'        =>  utils::post('banque'),
                'iban'          =>  utils::post('iban'),
                'bic'           =>  utils::post('bic'),
            ));

            utils::redirect('/admin/compta/banques/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->display('admin/compta/banques/ajouter.tpl');

?>