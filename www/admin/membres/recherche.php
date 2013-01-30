<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$recherche = trim(utils::get('r'));
$champ = trim(utils::get('c'));

$champs = $config->get('champs_membres');

if (!$champ)
{
    if (is_numeric(trim($recherche))) {
        $champ = 'id';
    }
    elseif (strpos($recherche, '@') !== false) {
        $champ = 'email';
    }
    elseif ($champs->get('nom')) {
        $champ = 'nom';
    }
    else {
        $champ = $champs->getFirst();
    }
}
else
{
    if ($champ != 'id' && !$champs->get($champ))
    {
        throw new UserException('Le champ demandé n\'existe pas.');
    }
}

$champs_liste = $champs->getList();

$champs_liste = array_merge(
    array('id' => array('title' => 'Numéro unique')),
    $champs_liste
);

$champs_entete = $champs->getListedFields();

if (!array_key_exists($champ, $champs_entete))
{
    $champs_entete = array_merge(
        array($champ => $champs_liste[$champ]),
        $champs_entete
    );
}

$tpl->assign('champs_entete', $champs_entete);
$tpl->assign('champs_liste', $champs_liste);
$tpl->assign('champ', $champ);
$tpl->assign('liste', $membres->search($champ, $recherche));

$tpl->assign('recherche', $recherche);

$tpl->display('admin/membres/recherche.tpl');

?>