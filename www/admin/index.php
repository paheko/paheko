<?php

require_once __DIR__ . '/_inc.php';

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;
$categorie = $cats->get($user['id_categorie']);

$tpl->assign('categorie', $categorie);
$tpl->assign('verif_cotisation', Garradin_Membres::checkCotisation($user['date_cotisation'], $categorie['duree_cotisation']));

$tpl->assign('garradin_version', garradin_version());

$tpl->display('admin/index.tpl');

?>