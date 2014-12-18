<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$recherche = trim(Utils::get('r'));
$champ = trim(Utils::get('c'));

$champs = $config->get('champs_membres');

$auto = false;

// On détermine magiquement quel champ on recherche
if (!$champ)
{
    $auto = true;

    if (is_numeric(trim($recherche))) {
        $champ = 'id';
    }
    elseif (strpos($recherche, '@') !== false) {
        $champ = 'email';
    }
    else {
        $champ = $config->get('champ_identite');
    }
}
else
{
    if ($champ != 'id' && !$champs->get($champ))
    {
        throw new UserException('Le champ demandé n\'existe pas.');
    }
}

if ($recherche != '')
{
    $result = $membres->search($champ, $recherche);

    if (count($result) == 1 && $auto)
    {
        Utils::redirect('/admin/membres/fiche.php?id=' . (int)$result[0]['id']);
    }
}

$champs_liste = $champs->getList();

$champs_liste = array_merge(
    ['id' => ['title' => 'Numéro unique', 'type' => 'number']],
    $champs_liste
);

$champs_entete = $champs->getListedFields();

if (!array_key_exists($champ, $champs_entete))
{
    $champs_entete = array_merge(
        [$champ => $champs_liste[$champ]],
        $champs_entete
    );
}

$tpl->assign('champs_entete', $champs_entete);
$tpl->assign('champs_liste', $champs_liste);
$tpl->assign('champ', $champ);

if ($recherche != '')
{
    $tpl->assign('liste', $result);
}

$tpl->assign('recherche', $recherche);

$tpl->display('admin/membres/recherche.tpl');

?>