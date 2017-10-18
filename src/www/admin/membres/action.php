<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

if (!f('selected') || !is_array(f('selected')) || !count(f('selected')))
{
    throw new UserException("Aucun membre sélectionné.");
}

foreach (f('selected') as &$id)
{
    $id = (int) $id;

    // On ne permet pas d'action collective sur l'utilisateur courant pour éviter les risques
    // d'erreur genre "oh je me suis supprimé du coup j'ai plus accès à rien"
    if ($id == $user->id)
    {
        throw new UserException("Il n'est pas possible de se modifier ou supprimer soi-même.");
    }
}

$action = f('action') ?: (f('move') ? 'move' : (f('delete') ? 'delete' : ''));

if (!$action)
{
    throw new UserException('Aucune action sélectionnée.');
}

if ($action == 'move' && f('confirm'))
{
    $form->check('membres_action', [
        'selected' => 'required|array',
        'id_categorie' => 'required|numeric',
    ]);

    if (!$form->hasErrors())
    {
        $membres->changeCategorie(f('id_categorie'), f('selected'));
        Utils::redirect('/admin/membres/');
    }
}
elseif ($action == 'delete' && f('confirm'))
{
    $form->check('membres_action', [
        'selected' => 'required|array',
    ]);

    if (!$form->hasErrors())
    {
        $membres->delete(f('selected'));
        Utils::redirect('/admin/membres/');
    }
}

$tpl->assign('selected', f('selected'));
$tpl->assign('nb_selected', count(f('selected')));

if ($action == 'move')
{
    $cats = new Membres\Categories;

    $tpl->assign('membres_cats', $cats->listSimple());
}

$tpl->assign('action', $action);

$tpl->display('admin/membres/action.tpl');
