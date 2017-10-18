<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

$membre = false;

if (($id = qg('id')) && is_numeric($id))
{
    $membre = $membres->get((int) $id);

    if (!$membre)
    {
        throw new UserException("Ce membre n'existe pas.");
    }

    $cats = new Membres\Categories;
    $categorie = $cats->get($membre->id_categorie);
}
else
{
    $categorie = ['id_cotisation_obligatoire' => false];
}

$cotisations = new Cotisations;
$m_cotisations = new Membres\Cotisations;

$cats = new Compta\Categories;
$banques = new Compta\Comptes_Bancaires;

if (f('add'))
{
    $form->check('add_cotisation', [
        'date'          => 'date_format:Y-m-d|required',
        'id_cotisation' => 'numeric|required|in_table:cotisations,id',
        'id_membre'     => 'numeric|required|in_table:membres,id',
    ]);

    if (!$form->hasErrors())
    {
        try {
            $id_membre = f('id_membre');

            if (!$id_membre && f('numero_membre'))
            {
                $id_membre = (new Membres)->getIDWithNumero(f('numero_membre'));
            }

            $data = [
                'date'          =>  f('date'),
                'id_cotisation' =>  f('id_cotisation'),
                'id_membre'     =>  $id_membre,
                'id_auteur'     =>  $user->id,
            ];

            $compta = [
                'montant'        =>  f('montant'),
                'moyen_paiement' =>  f('moyen_paiement'),
                'numero_cheque'  =>  f('numero_cheque'),
                'banque'         =>  f('banque'),
                'numero_piece'   =>  f('numero_piece'),
                'remarques'      =>  f('remarques'),
                'a_encaisser'    =>  f('a_encaisser'),
            ];

            $m_cotisations->add($data, $compta);

            Utils::redirect('/admin/membres/cotisations.php?id=' . $id_membre);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('membre', $membre);

$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->assign('default_co', null);
$tpl->assign('default_amount', 0.00);
$tpl->assign('default_date', date('Y-m-d'));
$tpl->assign('default_compta', null);

$tpl->assign('moyens_paiement', $cats->listMoyensPaiement());
$tpl->assign('moyen_paiement', f('moyen_paiement') ?: 'ES');
$tpl->assign('comptes_bancaires', $banques->getList());
$tpl->assign('banque', f('banque'));


if (qg('cotisation'))
{
    $co = $cotisations->get(qg('cotisation'));

    if (!$co)
    {
        throw new UserException("La cotisation indiquée en paramètre n'existe pas.");
    }

    $tpl->assign('default_co', $co->id);
    $tpl->assign('default_compta', $co->id_categorie_compta);
    $tpl->assign('default_amount', $co->montant);
}
elseif ($membre)
{
    if (!empty($categorie->id_cotisation_obligatoire))
    {
        $co = $cotisations->get($categorie->id_cotisation_obligatoire);

        $tpl->assign('default_co', $co->id);
        $tpl->assign('default_amount', $co->montant);
    }
}

$tpl->display('admin/membres/cotisations/ajout.tpl');
