{include file="admin/_head.tpl" title="`$membre.identite` (`$categorie.nom`)" current="membres"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}"><b>{$membre.identite|escape}</b></a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN && $user.id != $membre.id}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
    {/if}
    <li><a href="{$admin_url}membres/cotisations.php?id={$membre.id|escape}">Suivi des cotisations</a></li>
</ul>

<dl class="cotisation">
{if $cotisation}
    <dt>Cotisation obligatoire</dt>
    <dd>{$cotisation.intitule|escape} — 
        {if $cotisation.duree}
            {$cotisation.duree|escape} jours
        {elseif $cotisation.debut}
            du {$cotisation.debut|format_sqlite_date_to_french} au {$cotisation.fin|format_sqlite_date_to_french}
        {else}
            ponctuelle
        {/if}
        — {$cotisation.montant|escape_money} {$config.monnaie|escape}
    </dd>
    <dt>À jour de cotisation ?</dt>
    <dd>
        {if !$cotisation.a_jour}
            <span class="error"><b>Non</b>, cotisation non payée</span>
        {else}
            <b class="confirm">&#10003; Oui</b>
            {if $cotisation.expiration}
                (expire le {$cotisation.expiration|format_sqlite_date_to_french})
            {/if}
        {/if}
    </dd>
{/if}
    <dt>
        {if $nb_activites == 1}
            {$nb_activites|escape} cotisation enregistrée
        {elseif $nb_activites}
            {$nb_activites|escape} cotisations enregistrées
        {else}
            Aucune cotisation enregistrée
        {/if} 
    </dt>
    <dd>
        <a href="{$admin_url}membres/cotisations.php?id={$membre.id|escape}">Voir l'historique</a>
    </dd>
    <dd><form method="get" action="{$admin_url}membres/cotisations/ajout.php"><input type="submit" value="Enregistrer une cotisation &rarr;" /><input type="hidden" name="id" value="{$membre.id|escape}" /></form></dd>
{if !empty($nb_operations)}
    <dt>Écritures comptables</dt>
    <dd>{$nb_operations|escape} écritures comptables
        — <a href="{$admin_url}compta/operations/membre.php?id={$membre.id|escape}">Voir la liste des écritures ajoutées par ce membre</a>
    </dd>
 {/if}
</dl>

<dl class="describe">
    <dt>Numéro d'adhérent</dt>
    <dd>{$membre.id|escape}</dd>
    <dt>Catégorie</dt>
    <dd>{$categorie.nom|escape} <span class="droits">{format_droits droits=$categorie}</span></dd>
    <dt>Inscription</dt>
    <dd>{$membre.date_inscription|date_fr:'d/m/Y'}</dd>
    <dt>Dernière connexion</dt>
    <dd>{if empty($membre.date_connexion)}Jamais{else}{$membre.date_connexion|date_fr:'d/m/Y à H:i'}{/if}</dd>
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
            | <a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}"><b class="icn action">✉</b> Envoyer un message</a>
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
</dl>

{include file="admin/_foot.tpl"}