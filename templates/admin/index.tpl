{include file="admin/_head.tpl" title="Bonjour `$user.nom` !" current="home"}

<div class="infos_asso">
    <h3>{$config.nom_asso|escape}</h3>
    {if !empty($config.adresse_asso)}
    <p>
        {$config.adresse_asso|escape|nl2br}
    </p>
    {/if}
    {if !empty($config.email_asso)}
    <p>
        E-Mail : {mailto address=$config.email_asso}
    </p>
    {/if}
    {if !empty($config.site_asso)}
    <p>
        Web : <a href="{$config.site_asso|escape}">{$config.site_asso|escape}</a>
    </p>
    {/if}
</div>

<ul class="actions">
    <li><a href="{$admin_url}mes_infos.php">Modifier mes informations personnelles</a></li>
    <li>Cotisation : 
        {if empty($user.date_cotisation)}<b class="error">jamais réglée</b>
        {elseif $verif_cotisation === true}<b class="confirm">À jour :-)</b>
        {else}<b class="alert">En retard !</b>{/if}
    </li>
</ul>

<div class="wikiContent">
    {$page.contenu.contenu|format_wiki|liens_wiki:'?'}
</div>

{include file="admin/_foot.tpl"}