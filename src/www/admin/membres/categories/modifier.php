<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$cats = new Membres\Categories;

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$cat = $cats->get($id);

if (!$cat)
{
    throw new UserException("Cette catégorie n'existe pas.");
}

if (f('save'))
{
    $droits = implode(',', [
        Membres::DROIT_AUCUN,
        Membres::DROIT_ACCES,
        Membres::DROIT_ECRITURE,
        Membres::DROIT_ADMIN,
    ]);

    $form->check('edit_cat_' . $id, [
        'nom'                       => 'required',
        'droit_wiki'                => 'in:' . $droits,
        'droit_compta'              => 'in:' . $droits,
        'droit_membres'             => 'in:' . $droits,
        'droit_config'              => sprintf('in:%s,%s', Membres::DROIT_ADMIN, Membres::DROIT_AUCUN),
        'droit_connexion'           => sprintf('in:%s,%s', Membres::DROIT_ACCES, Membres::DROIT_AUCUN),
        'droit_inscription'         => sprintf('in:%s,%s', Membres::DROIT_ACCES, Membres::DROIT_AUCUN),
        'cacher'                    => 'boolean',
        'id_cotisation_obligatoire' => 'numeric',
    ]);

    if (!$form->hasErrors())
    {
        $data = [
            'nom'           =>  f('nom'),
            'description'   =>  f('description'),
            'droit_wiki'    =>  (int) f('droit_wiki'),
            'droit_compta'  =>  (int) f('droit_compta'),
            'droit_config'  =>  (int) f('droit_config'),
            'droit_membres' =>  (int) f('droit_membres'),
            'droit_connexion' => (int) f('droit_connexion'),
            'droit_inscription' => (int) f('droit_inscription'),
            'cacher'        =>  (int) f('cacher'),
            'id_cotisation_obligatoire' => (int) f('id_cotisation_obligatoire'),
        ];

        // Ne pas permettre de modifier la connexion, l'accès à la config et à la gestion des membres
        // pour la catégorie du membre qui édite les catégories, sinon il pourrait s'empêcher
        // de se connecter ou n'avoir aucune catégorie avec le droit de modifier les catégories !
        if ($cat->id == $user->id_categorie)
        {
            $data['droit_connexion'] = Membres::DROIT_ACCES;
            $data['droit_config'] = Membres::DROIT_ADMIN;
            $data['droit_membres'] = Membres::DROIT_ADMIN;
        }

        try {
            $cats->edit($id, $data);

            if ($id == $user->id_categorie)
            {
                // Mise à jour de la session courante
                $session->refresh();
            }

            Utils::redirect('/admin/membres/categories/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('cat', $cat);

$tpl->assign('readonly', $cat->id == $user->id_categorie ? 'disabled="disabled"' : '');

$cotisations = new Cotisations;
$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->assign('membres', $membres);

$tpl->display('admin/membres/categories/modifier.tpl');
