{include file="admin/_head.tpl" title="%s (%s)"|args:$membre.identite:$categorie.nom current="membres"}

<nav class="tabs">
    <ul>
        <li class="current"><a href="{$admin_url}membres/fiche.php?id={$membre.id}">{$membre.identite}</a></li>
        {if $session->canAccess('membres', Membres::DROIT_ECRITURE)}<li><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>{/if}
        {if $session->canAccess('membres', Membres::DROIT_ADMIN) && $user.id != $membre.id}
            <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
        {/if}
    </ul>
</nav>

<dl class="cotisation">
    <dt>Activités et cotisations</dt>
    {foreach from=$services item="service"}
    <dd>
        {$service.label}
        {if $service.status == -1 && $service.end_date} — terminée
        {elseif $service.status == -1} — <b class="error">en retard</b>
        {elseif $service.status == 1 && $service.end_date} — <b class="confirm">en cours</b>
        {elseif $service.status == 1} — <b class="confirm">à jour</b>{/if}
        {if $service.status.expiry_date} — expire le {$service.expiry_date|date_short}{/if}
        {if !$service.paid} — <b class="error">À payer&nbsp;!</b>{/if}
    </dd>
    {foreachelse}
    <dd>
        Ce membre n'est inscrit à aucune activité ou cotisation.
    </dd>
    {/foreach}
    <dd>
        {if count($services)}
            {linkbutton href="!services/user.php?id=%d"|args:$membre.id label="Liste des inscriptions aux activités" shape="menu"}
        {/if}
        {if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
            {linkbutton href="!services/save.php?user=%d"|args:$membre.id label="Inscrire à une activité" shape="plus"}
        {/if}
    </dd>
    {if $session->canAccess('membres', Membres::DROIT_ACCES)}
        {if !empty($transactions_linked)}
            <dt>Écritures comptables liées</dt>
            <dd><a href="{$admin_url}acc/transactions/user.php?id={$membre.id}">{$transactions_linked} écritures comptables liées à ce membre</a></dd>
        {/if}
        {if !empty($transactions_created)}
            <dt>Écritures comptables créées</dt>
            <dd><a href="{$admin_url}acc/transactions/creator.php?id={$membre.id}">{$transactions_created} écritures comptables créées par ce membre</a></dd>
        {/if}
    {/if}
</dl>

<aside class="describe">
	<dl class="describe">
		<dt>Catégorie</dt>
		<dd>{$categorie.nom} <span class="droits">{format_droits droits=$categorie}</span></dd>
		<dt>Inscription</dt>
		<dd>{$membre.date_inscription|date_short}</dd>
		<dt>Dernière connexion</dt>
		<dd>{if empty($membre.date_connexion)}Jamais{else}{$membre.date_connexion|date_long}{/if}</dd>
		<dt>Mot de passe</dt>
		<dd>
			{if empty($membre.passe)}
				Pas de mot de passe configuré
			{else}
				<b class="icn">☑</b> Oui
				{if !empty($membre.secret_otp)}
					(<b class="icn">🔒</b> avec second facteur)
				{else}
					(<b class="icn">🔓</b> sans second facteur)
				{/if}
		{/if}
		</dd>
	</dl>
</aside>

<dl class="describe">
    {foreach from=$champs key="c" item="c_config"}
    <dt>{$c_config.title}</dt>
    <dd>
        {if $c_config.type == 'checkbox'}
            {if $membre->$c}Oui{else}Non{/if}
        {elseif empty($membre->$c)}
            <em>(Non renseigné)</em>
        {elseif $c == $c_config.champ_identite}
            <strong>{$membre->$c}</strong>
        {elseif $c_config.type == 'email'}
            <a href="mailto:{$membre->$c|escape:'url'}">{$membre->$c}</a>
            {if $c == 'email'}
                {linkbutton href="!membres/message.php?id=%d"|args:$membre.id label="Envoyer un message" shape="mail"}
            {/if}
        {elseif $c_config.type == 'tel'}
            <a href="tel:{$membre->$c}">{$membre->$c|format_tel}</a>
        {elseif $c_config.type == 'country'}
            {$membre->$c|get_country_name}
        {elseif $c_config.type == 'date'}
            {$membre->$c|date_short}
        {elseif $c_config.type == 'datetime'}
            {$membre->$c|date_fr}
        {elseif $c_config.type == 'password'}
            *******
        {elseif $c_config.type == 'multiple'}
            <ul>
            {foreach from=$c_config.options key="b" item="name"}
                {if $membre->$c & (0x01 << $b)}
                    <li>{$name}</li>
                {/if}
            {/foreach}
            </ul>
        {else}
            {$membre->$c|escape|rtrim|nl2br}
        {/if}
    </dd>
    {/foreach}
</dl>

{include file="admin/_foot.tpl"}