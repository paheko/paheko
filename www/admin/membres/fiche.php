<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits'] < Garradin_Membres::DROIT_ADMIN)
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

$error = false;

if (!empty($_POST['cotisation']))
{
    if (!utils::CSRF_check('cotisation_'.$id))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $membres->updateCotisation($id, utils::post('date'));

            if ($id == $user['id'])
            {
                $membres->updateSessionData();
            }

            utils::redirect('/admin/membres/fiche.php?id='.$id);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;
$categorie = $cats->get($membre['id_categorie']);

$tpl->assign('categorie', $categorie);
$tpl->assign('membre', $membre);
$tpl->assign('verif_cotisation', Garradin_Membres::checkCotisation($membre['date_cotisation'], $categorie['duree_cotisation']));

if (!empty($membre['date_cotisation']))
{
    $prochaine_cotisation = new DateTime('@'.$membre['date_cotisation']);
    $prochaine_cotisation->modify('+1 year');
    $prochaine_cotisation = $prochaine_cotisation->getTimestamp();
}
else
{
    $prochaine_cotisation = time();
}
$tpl->assign('date_cotisation_defaut', date('d/m/Y', $prochaine_cotisation));

$tpl->assign('error', $error);

$tpl->display('admin/membres/fiche.tpl');

?>