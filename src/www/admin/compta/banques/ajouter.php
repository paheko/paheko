<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$banque = new Compta\Comptes_Bancaires;

$error = false;

if (!empty($_POST['add']))
{
    if (!Utils::CSRF_check('compta_ajout_banque'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $banque->add([
                'libelle'       =>  Utils::post('libelle'),
                'banque'        =>  Utils::post('banque'),
                'iban'          =>  Utils::post('iban'),
                'bic'           =>  Utils::post('bic'),
            ]);

            Utils::redirect('/admin/compta/banques/');
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