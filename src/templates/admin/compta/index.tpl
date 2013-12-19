{include file="admin/_head.tpl" title="Comptabilité" current="compta"}

{if $user.droits.compta >= Garradin\Membres::DROIT_ADMIN}
<ul class="actions">
    <li><a href="{$www_url}admin/compta/import.php">Import / export</a></li>
    <li><a href="{$www_url}admin/compta/operations/recherche_sql.php">Recherche par requête SQL</a></li>
</ul>
{/if}

<div class="infos">
    <p>
        <img src="{$www_url}admin/compta/graph.php?g=recettes_depenses" />
        <img src="{$www_url}admin/compta/graph.php?g=banques_caisses" />
    </p>
    <p>
        <img src="{$www_url}admin/compta/pie.php?g=recettes" />
        <img src="{$www_url}admin/compta/pie.php?g=depenses" />
    </p>
</div>

{include file="admin/_foot.tpl"}