<?php

require_once __DIR__ . '/../_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_comptes_bancaires.php';
$banques = new Garradin_Compta_Comptes_Bancaires;

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$liste = $banques->getList();

foreach ($liste as &$banque)
{
    $banque['solde'] = $journal->getSolde($banque['id']);
}

$tpl->assign('liste', $liste);

function tpl_format_iban($iban)
{
    return implode(' ', str_split($iban, 4));
}

function tpl_format_rib($iban)
{
    if (substr($iban, 0, 2) != 'FR')
        return '';

    $rib = utils::IBAN_RIB($iban);
    $rib = explode(' ', $rib);

    $out = '<table class="rib"><thead><tr><th>Banque</th><th>Guichet</th><th>Compte</th><th>Clé</th></tr></thead>';
    $out.= '<tbody><tr><td>'.$rib[0].'</td><td>'.$rib[1].'</td><td>'.$rib[2].'</td><td>'.$rib[3].'</td></tr></tbody></table>';
    return $out;
}

$tpl->register_modifier('format_iban', 'tpl_format_iban');
$tpl->register_modifier('format_rib', 'tpl_format_rib');

$tpl->display('admin/compta/banques/index.tpl');

?>