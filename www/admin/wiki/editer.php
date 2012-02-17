<?php

require_once __DIR__ . '/_inc.php';

if (!utils::get('id') || !is_numeric(utils::get('id')))
{
    throw new UserException('Numéro de page invalide.');
}

$page = $wiki->getById(utils::get('id'));
$error = false;

if (!$page)
{
    throw new UserException('Page introuvable.');
}

if (!empty($page['contenu']))
{
    $page['contenu'] = $page['contenu']['contenu'];
}

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('wiki_edit_'.$page['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif ($page['date_modification'] > (int) utils::post('debut_edition'))
    {
        $error = 'La page a été modifiée par quelqu\'un d\'autre depuis que vous avez commencé l\'édition.';
    }
    else
    {
        try {
            $wiki->edit($page['id'], array(
                'titre'         =>  utils::post('titre'),
                'uri'           =>  utils::post('uri'),
                'parent'        =>  utils::post('parent'),
                'droit_lecture' =>  utils::post('droit_lecture'),
                'droit_ecriture'=>  utils::post('droit_ecriture'),
            ));

            $wiki->editRevision($page['id'], (int) utils::post('revision_edition'), array(
                'contenu'       =>  utils::post('contenu'),
                'modification'  =>  utils::post('modification'),
                'id_auteur'     =>  $user['id'],
            ));

            $page = $wiki->getById($page['id']);

            utils::redirect('/admin/wiki/?'.$page['uri']);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('page', $page);

$tpl->assign('time', time());

$tpl->display('admin/wiki/editer.tpl');

?>