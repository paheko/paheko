<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

if (!qg('id') || !is_numeric(qg('id')))
{
    throw new UserException("Argument du numéro de cotisation manquant.");
}

$cotisations = new Cotisations;

$co = $cotisations->get(qg('id'));
$cats = new Compta\Categories;

if (!$co)
{
    throw new UserException("Cette cotisation n'existe pas.");
}

if (f('save') && $form->check('edit_co_' . $co->id))
{
    try {
        $duree = f('periodicite') == 'jours' ? (int) f('duree') : null;
        $debut = f('periodicite') == 'date' ? f('debut') : null;
        $fin = f('periodicite') == 'date' ? f('fin') : null;
        $id_cat = f('categorie') ? (int) f('id_categorie_compta') : null;

        $cotisations->edit($co->id, [
            'intitule'            => f('intitule'),
            'description'         => f('description'),
            'montant'             => (float) f('montant'),
            'duree'               => $duree,
            'debut'               => $debut,
            'fin'                 => $fin,
            'id_categorie_compta' => $id_cat,
        ]);

        Utils::redirect('/admin/membres/cotisations/');
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}


$co->periodicite = $co->duree ? 'jours' : ($co->debut ? 'date' : 'ponctuel');
$co->categorie = $co->id_categorie_compta ? 1 : 0;

$tpl->assign('cotisation', $co);
$tpl->assign('categories', $cats->getList(Compta\Categories::RECETTES));

$tpl->display('admin/membres/cotisations/gestion/modifier.tpl');
