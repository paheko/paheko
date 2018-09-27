{include file="admin/_head.tpl" title="Comptabilité" current="compta"}

{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
<ul class="actions">
    <li><a href="{$admin_url}compta/import.php">Import / export</a></li>
    <li><a href="{$admin_url}compta/operations/recherche_sql.php">Recherche par requête SQL</a></li>
</ul>
{/if}

<div class="infos">
    <p>
        <img src="{$admin_url}compta/graph.php?g=recettes_depenses" />
        <img src="{$admin_url}compta/graph.php?g=banques_caisses" />
    </p>
    <p>
        <img src="{$admin_url}compta/pie.php?g=recettes" />
        <img src="{$admin_url}compta/pie.php?g=depenses" />
    </p>
</div>

{include file="admin/_foot.tpl"}