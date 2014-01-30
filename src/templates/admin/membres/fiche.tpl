{include file="admin/_head.tpl" title="`$membre.nom` (`$categorie.nom`)" current="membres"}

{if $user.droits.membres >= Garradin\Membres::DROIT_ECRITURE}
<ul class="actions">
    <li><a href="{$www_url}admin/membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$www_url}admin/membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
    {/if}
</ul>
{/if}

<dl class="describe">
    <dt>Numéro d'adhérent</dt>
    <dd>{$membre.id|escape}</dd>
    {foreach from=$champs key="c" item="config"}
    <dt>{$config.title|escape}</dt>
    <dd>
        {if $config.type == 'checkbox'}
            {if $membre[$c]}Oui{else}Non{/if}
        {elseif empty($membre[$c])}
            <em>(Non renseigné)</em>
        {elseif $c == 'nom'}
            <strong>{$membre[$c]|escape}</strong>
        {elseif $c == 'email'}
            <a href="mailto:{$membre[$c]|escape}">{$membre[$c]|escape}</a>
            | <a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">Envoyer un message</a>
        {elseif $config.type == 'email'}
            <a href="mailto:{$membre[$c]|escape}">{$membre[$c]|escape}</a>
        {elseif $config.type == 'tel'}
            <a href="tel:{$membre[$c]|escape}">{$membre[$c]|escape|format_tel}</a>
        {elseif $config.type == 'country'}
            {$membre[$c]|get_country_name|escape}
        {elseif $config.type == 'date' || $config.type == 'datetime'}
            {$membre[$c]|format_sqlite_date_to_french}
        {elseif $c == 'passe'}
            Oui
        {elseif $config.type == 'password'}
            *******
        {elseif $config.type == 'multiple'}
            <ul>
            {foreach from=$config.options key="b" item="name"}
                {if $membre[$c] & (0x01 << $b)}
                    <li>{$name|escape}</li>
                {/if}
            {/foreach}
            </ul>
        {else}
            {$membre[$c]|escape|rtrim|nl2br}
        {/if}
    </dd>
    {/foreach}
    <dt>Catégorie</dt>
    <dd>{$categorie.nom|escape} <span class="droits">{format_droits droits=$categorie}</span></dd>
    <dt>Inscription</dt>
    <dd>{$membre.date_inscription|date_fr:'d/m/Y'}</dd>
    <dt>Dernière connexion</dt>
    <dd>{if empty($membre.date_connexion)}Jamais{else}{$membre.date_connexion|date_fr:'d/m/Y à H:i'}{/if}</dd>
</dl>

{include file="admin/_foot.tpl"}