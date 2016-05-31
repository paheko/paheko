<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if ($user['droits']['wiki'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (!Utils::get('id') || !is_numeric(Utils::get('id')))
{
    throw new UserException('Numéro de page invalide.');
}

$page = $wiki->getById(Utils::get('id'));
$error = false;

if (!$page)
{
    throw new UserException('Page introuvable.');
}

if (!empty($page['contenu']))
{
    $page['chiffrement'] = $page['contenu']['chiffrement'];
    $page['contenu'] = $page['contenu']['contenu'];
}

if (Utils::post('date'))
{
    $date = Utils::post('date') . ' ' . sprintf('%02d:%02d', Utils::post('date_h'), Utils::post('date_min'));
}
else
{
    $date = false;
}

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('wiki_edit_'.$page['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif ($page['date_modification'] > (int) Utils::post('debut_edition'))
    {
        $error = 'La page a été modifiée par quelqu\'un d\'autre depuis que vous avez commencé l\'édition.';
    }
    else
    {
        try {
            $wiki->edit($page['id'], [
                'titre'         =>  Utils::post('titre'),
                'uri'           =>  Utils::post('uri'),
                'parent'        =>  Utils::post('parent'),
                'droit_lecture' =>  Utils::post('droit_lecture'),
                'droit_ecriture'=>  Utils::post('droit_ecriture'),
                'date_creation' =>  $date,
            ]);

            $wiki->editRevision($page['id'], (int) Utils::post('revision_edition'), [
                'contenu'       =>  Utils::post('contenu'),
                'modification'  =>  Utils::post('modification'),
                'id_auteur'     =>  $user['id'],
                'chiffrement'   =>  Utils::post('chiffrement'),
            ]);

            $page = $wiki->getById($page['id']);

            Utils::redirect('/admin/wiki/?'.$page['uri']);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$parent = (int) Utils::post('parent') ?: (int) $page['parent'];
$tpl->assign('parent', $parent ? $wiki->getTitle($parent) : 0);

$tpl->assign('error', $error);
$tpl->assign('page', $page);

$tpl->assign('time', time());
$tpl->assign('date', $date ? strtotime($date) : $page['date_creation']);

$tpl->assign('custom_js', ['wiki_editor.js', 'wiki-encryption.js']);

$tpl->display('admin/wiki/editer.tpl');