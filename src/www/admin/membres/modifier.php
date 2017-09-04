<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$membre = $membres->get($id);

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$cats = new Membres\Categories;
$champs = $config->get('champs_membres');

// Protection contre la modification des admins par des membres moins puissants
$membre_cat = $cats->get($membre->id_categorie);

if (($membre_cat->droit_membres == Membres::DROIT_ADMIN)
    && ($user->droit_membres < Membres::DROIT_ADMIN))
{
    throw new UserException("Seul un membre admin peut modifier un autre membre admin.");
}

if (f('save'))
{
    $form->check('edit_member_' . $id, [
        'passe' => 'confirmed',
        // FIXME: ajouter les règles pour les champs membres
    ]);

    if (!$form->hasErrors())
    {
        try {
            $data = [];

            foreach ($champs->getAll() as $key=>$config)
            {
                $data[$key] = f($key);
            }

            if ($session->canAccess('membres', Membres::DROIT_ADMIN) && $user->id != $membre->id)
            {
                $data['id_categorie'] = f('id_categorie');
                $data['id'] = f('id');
            }

            $membres->edit($id, $data);

            if (isset($data['id']) && $data['id'] != $id)
            {
                $id = (int)$data['id'];
            }

            Utils::redirect('/admin/membres/fiche.php?id='.(int)$id);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->assign('champs', $champs->getAll());

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', f('id_categorie') ?: $membre->id_categorie);

$tpl->assign('can_change_id', $session->canAccess('membres', Membres::DROIT_ADMIN));

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/modifier.tpl');
