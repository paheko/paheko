{include file="admin/_head.tpl" title="Bonjour %s !"|args:$user.identite current="home"}

{$banniere|raw}

<ul class="actions">
    <li><a href="{$admin_url}mes_infos.php">Modifier mes informations personnelles</a></li>
    <li><a href="{$admin_url}mes_cotisations.php">Suivi de mes cotisations</a></li>
</ul>


<aside class="describe">
    <h3>{$config.nom_asso}</h3>
    {if !empty($config.adresse_asso)}
    <p>
        {$config.adresse_asso|escape|nl2br}
    </p>
    {/if}
    {if !empty($config.email_asso)}
    <p>
        E-Mail : <a href="mailto:{$config.email_asso}">{$config.email_asso}</a>
    </p>
    {/if}
    {if !empty($config.site_asso)}
    <p>
        Web : <a href="{$config.site_asso}">{$config.site_asso}</a>
    </p>
    {/if}
</aside>

<div class="wikiContent">
    {$page.contenu.contenu|raw|format_wiki|liens_wiki:'wiki/?'}
</div>

{include file="admin/_foot.tpl"}