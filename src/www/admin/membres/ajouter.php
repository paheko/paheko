<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$cats = new Membres\Categories;
$champs = $config->get('champs_membres');

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('new_member'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (Utils::post('passe') != Utils::post('repasse'))
    {
        $error = 'La vérification ne correspond pas au mot de passe.';
    }
    else
    {
        try
        {
            if ($user['droits']['membres'] == Membres::DROIT_ADMIN)
            {
                $id_categorie = Utils::post('id_categorie');
            }
            else
            {
                $id_categorie = $config->get('categorie_membres');
            }

            $data = ['id_categorie' => $id_categorie];

            foreach ($champs->getAll() as $key=>$dismiss)
            {
                $data[$key] = Utils::post($key);
            }

            $id = $membres->add($data);

            Utils::redirect('/admin/membres/fiche.php?id='.(int)$id);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->assign('champs', $champs->getAll());

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', Utils::post('id_categorie') ?: $config->get('categorie_membres'));

$tpl->display('admin/membres/ajouter.tpl');

?>