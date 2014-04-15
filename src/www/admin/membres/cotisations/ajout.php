<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$membre = false;

if (!empty($_GET['id']) && is_numeric($_GET['id']))
{
    $membre = $membres->get((int) $_GET['id']);

    if (!$membre)
    {
        throw new UserException("Ce membre n'existe pas.");
    }

    $cats = new Membres_Categories;
    $categorie = $cats->get($membre['id_categorie']);
}
else
{
    $categorie = ['id_cotisation_obligatoire' => false];
}

$cotisations = new Cotisations;
$m_cotisations = new Cotisations_Membres;

$cats = new Compta_Categories;
$banques = new Compta_Comptes_Bancaires;

$error = false;

if (!empty($_POST['add']))
{
    if (!utils::CSRF_check('add_cotisation'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $data = [
                'date'              =>  utils::post('date'),
                'id_cotisation'     =>  utils::post('id_cotisation'),
                'id_membre'         =>  utils::post('id_membre'),
                'id_auteur'         =>  $user['id'],
                'montant'           =>  utils::post('montant'),
                'moyen_paiement'    =>  utils::post('moyen_paiement'),
                'numero_cheque'     =>  utils::post('numero_cheque'),
                'banque'            =>  utils::post('banque'),
            ];

            $m_cotisations->add($data);

            utils::redirect('/admin/membres/cotisations.php?id=' . (int)utils::post('id_membre'));
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('membre', $membre);

$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->assign('default_co', null);
$tpl->assign('default_amount', 0.00);
$tpl->assign('default_date', date('Y-m-d'));
$tpl->assign('default_compta', null);

$tpl->assign('moyens_paiement', $cats->listMoyensPaiement());
$tpl->assign('moyen_paiement', utils::post('moyen_paiement') ?: 'ES');
$tpl->assign('comptes_bancaires', $banques->getList());
$tpl->assign('banque', utils::post('banque'));


if (utils::get('cotisation'))
{
    $co = $cotisations->get(utils::get('cotisation'));

    if (!$co)
    {
        throw new UserException("La cotisation indiquée en paramètre n'existe pas.");
    }

    $tpl->assign('default_co', $co['id']);
    $tpl->assign('default_compta', $co['id_categorie_compta']);
    $tpl->assign('default_amount', $co['montant']);
}
elseif ($membre)
{
    if (!empty($categorie['id_cotisation_obligatoire']))
    {
        $co = $cotisations->get($categorie['id_cotisation_obligatoire']);

        $tpl->assign('default_co', $co['id']);
        $tpl->assign('default_amount', $co['montant']);
    }
}


$tpl->display('admin/membres/cotisations/ajout.tpl');

?>