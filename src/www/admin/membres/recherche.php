<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

$recherche = trim(qg('r'));
$champ = trim(qg('c'));

$champs = $config->get('champs_membres');

$auto = false;

// On détermine magiquement quel champ on recherche
if (!$champ)
{
    $auto = true;

    if (is_numeric(trim($recherche))) {
        $champ = 'numero';
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
    if ($champ != 'numero' && !$champs->get($champ))
    {
        throw new UserException('Le champ demandé n\'existe pas.');
    }
}

if ($recherche != '')
{
    $result = $membres->search($champ, $recherche);

    if (count($result) == 1 && $auto)
    {
        Utils::redirect('/admin/membres/fiche.php?id=' . (int)$result[0]->id);
    }
}

$champs_liste = $champs->getList();
$champs_entete = $champs->getListedFields();

if (!isset($champs_entete->$champ))
{
    $champs_entete = array_merge(
        [$champ => $champs_liste->$champ],
        (array)$champs_entete
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
