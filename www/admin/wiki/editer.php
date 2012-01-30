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

$tpl->assign('error', $error);
$tpl->assign('page', $page);

$tpl->display('admin/wiki/editer.tpl');

?>