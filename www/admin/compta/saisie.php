<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';

$journal = new Garradin_Compta_Journal;

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('compta_saisie'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $journal->add(array(
                'id_categorie'  =>  $id_categorie,
                'nom'           =>  utils::post('nom'),
                'email'         =>  utils::post('email'),
                'passe'         =>  utils::post('passe'),
                'telephone'     =>  utils::post('telephone'),
                'code_postal'   =>  utils::post('code_postal'),
                'adresse'       =>  utils::post('adresse'),
                'ville'         =>  utils::post('ville'),
                'pays'          =>  utils::post('pays'),
                'date_naissance'=>  utils::post('date_naissance'),
                'notes'         =>  '',
                'lettre_infos'  =>  utils::post('lettre_infos'),
            ));

            utils::redirect('/admin/compta/operation.php?id='.(int)$id);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('comptes', $comptes->listTree());

$tpl->display('admin/compta/saisie.tpl');

?>