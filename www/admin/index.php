<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$cats = new Membres_Categories;
$categorie = $cats->get($user['id_categorie']);

$tpl->assign('categorie', $categorie);
$tpl->assign('verif_cotisation', Membres::checkCotisation($user['id']));

$wiki = new Wiki;
$page = $wiki->getByURI($config->get('accueil_connexion'));
$tpl->assign('page', $page);

$tpl->display('admin/index.tpl');

?>