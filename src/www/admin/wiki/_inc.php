<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('wiki', Membres::DROIT_ACCES);

$wiki = new Wiki;
$wiki->setRestrictionCategorie($user->id_categorie, $user->droit_wiki);

$tpl->assign('custom_css', ['wiki.css']);