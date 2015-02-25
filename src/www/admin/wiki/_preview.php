<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if ($user['droits']['wiki'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$tpl->assign('contenu', Utils::post('contenu'));

$tpl->display('admin/wiki/_preview.tpl');