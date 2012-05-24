{include file="admin/_head.tpl" title=$config.nom_asso current="home"}

<div class="infos">
    {if !empty($config.adresse_asso)}
    <p>
        {$config.adresse_asso|escape|nl2br}
    </p>
    {/if}
    {if !empty($config.email_asso)}
    <p>
        Nous contacter : {mailto address=$config.email_asso}
    </p>
    {/if}
    {if !empty($config.site_asso)}
    <p>
        Notre site : <a href="{$config.site_asso|escape}">{$config.site_asso|escape}</a>
    </p>
    {/if}

    <h3>Bienvenue, {$user.nom|escape} !</h3>
{if empty($user.date_cotisation)}
    <p class="error">Vous n'avez jamais réglé votre cotisation.</p>
{elseif $verif_cotisation === true}
    <p class="confirm">Cotisation réglée le {$user.date_cotisation|date_fr:'d/m/Y'} :-)</p>
{else}
    <p class="alert">Cotisation en retard ! (dernier règlement le {$user.date_cotisation|date_fr:'d/m/Y'})</p>
{/if}
</div>

{include file="admin/_foot.tpl"}