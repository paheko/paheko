<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de membre manquant.");
}

$id = (int) $_GET['id'];

$membre = $membres->get($id);

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$cats = new Membres_Categories;
$champs = $config->get('champs_membres');

// Protection contre la modification des admins par des membres moins puissants
$membre_cat = $cats->get($membre['id_categorie']);
if (($membre_cat['droit_membres'] == Membres::DROIT_ADMIN)
    && ($user['droits']['membres'] < Membres::DROIT_ADMIN))
{
    throw new UserException("Seul un membre admin peut modifier un autre membre admin.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('edit_member_'.$id))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (utils::post('passe') != utils::post('repasse'))
    {
        $error = 'La vérification ne correspond pas au mot de passe.';
    }
    else
    {
        try {
            $data = array();

            foreach ($champs->getAll() as $key=>$config)
            {
                $data[$key] = utils::post($key);
            }

            if ($user['droits']['membres'] == Membres::DROIT_ADMIN)
            {
                $data['id_categorie'] = utils::post('id_categorie');
                $data['id'] = utils::post('id');
            }

            $membres->edit($id, $data, ($user['droits']['membres'] == Membres::DROIT_ADMIN) ? false : true);

            utils::redirect('/admin/membres/fiche.php?id='.(int)$id);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('passphrase', utils::suggestPassword());
$tpl->assign('champs', $champs->getAll());

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', utils::post('id_categorie') ?: $membre['id_categorie']);

$tpl->assign('can_change_id', $user['droits']['membres'] == Membres::DROIT_ADMIN);

$tpl->assign('membre', $membre);

$tpl->assign('custom_js', array('datepickr.js'));

$tpl->display('admin/membres/modifier.tpl');

?>