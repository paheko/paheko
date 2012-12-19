<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$cats = new Membres_Categories;
$categorie = $cats->get($user['id_categorie']);

$tpl->assign('categorie', $categorie);
$tpl->assign('verif_cotisation', Membres::checkCotisation($user['date_cotisation'], $categorie['duree_cotisation']));

$tpl->display('admin/index.tpl');

?>