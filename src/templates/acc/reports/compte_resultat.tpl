{include file="admin/_head.tpl" title="Compte de résultat" current="compta/exercices" body_id="rapport"}

{include file="admin/compta/rapports/_header.tpl"}

{include file="admin/compta/rapports/_table_resultat.tpl" comptes=$compte_resultat header=true result=true}

{if !empty($compte_nature.charges.comptes)}
    <h2 class="ruler">Contributions en nature</h2>
    {include file="admin/compta/rapports/_table_resultat.tpl" comptes=$compte_nature header=false result=false}
{/if}

<p class="help">Toutes les opérations sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}