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

// Ne pas modifier le membre courant, on risque de se tirer une balle dans le pied
if ($membre->id == $user->id) {
    throw new UserException("Vous ne pouvez pas modifier votre propre profil, la modification doit être faite par un autre membre.");
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

            if (f('clear_otp')) {
                $data['secret_otp'] = null;
            }

            if (f('clear_pgp')) {
                $data['clef_pgp'] = null;
            }

            $membres->edit($id, $data);

            if (isset($data['id']) && $data['id'] != $id)
            {
                $id = (int)$data['id'];
            }

            Utils::redirect(ADMIN_URL . 'membres/fiche.php?id='.(int)$id);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$config = Config::getInstance();
$tpl->assign('id_field_name', $config->get('champ_identifiant'));
$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->assign('champs', $champs->getAll());

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', f('id_categorie') ?: $membre->id_categorie);

$tpl->assign('can_change_id', $session->canAccess('membres', Membres::DROIT_ADMIN));

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/modifier.tpl');
