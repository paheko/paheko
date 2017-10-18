<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

$cats = new Membres\Categories;
$champs = $config->get('champs_membres');

if (f('save'))
{
    $form->check('new_member', [
        'passe' => 'confirmed',
        // FIXME: ajouter les règles pour les champs membres
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            if ($session->canAccess('membres', Membres::DROIT_ADMIN))
            {
                $id_categorie = f('id_categorie');
            }
            else
            {
                $id_categorie = $config->get('categorie_membres');
            }

            $data = ['id_categorie' => $id_categorie];

            foreach ($champs->getAll() as $key=>$dismiss)
            {
                $data[$key] = f($key);
            }

            $id = $membres->add($data);

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
$tpl->assign('current_cat', f('id_categorie') ?: $config->get('categorie_membres'));

$tpl->display('admin/membres/ajouter.tpl');
