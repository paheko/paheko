<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$banques = new Compta_Comptes_Bancaires;
$journal = new Compta_Journal;

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

$tpl->register_modifier('format_iban', 'Garradin\tpl_format_iban');
$tpl->register_modifier('format_rib', 'Garradin\tpl_format_rib');

$tpl->display('admin/compta/banques/index.tpl');

?>