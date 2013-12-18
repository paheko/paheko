{include file="admin/_head.tpl" title="Comptabilité" current="compta"}

{if $user.droits.compta >= Garradin\Membres::DROIT_ADMIN}
<ul class="actions">
    <li><a href="{$www_url}admin/compta/import.php">Import / export</a></li>
    <li><a href="{$www_url}admin/compta/operations/recherche_sql.php">Recherche par requête SQL</a></li>
</ul>
{/if}

<p>
    <img src="{$www_url}admin/compta/graph.php?g=recettes_depenses" />
    <img src="{$www_url}admin/compta/graph.php?g=banques_caisses" />
    <img src="{$www_url}admin/compta/graph.php?g=dettes" />
</p>

{include file="admin/_foot.tpl"}