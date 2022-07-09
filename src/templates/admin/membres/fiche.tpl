{include file="admin/_head.tpl" title="%s (%s)"|args:$membre.identite:$category.name current="membres"}

<nav class="tabs">
    <ul>
        <li class="current"><a href="{$admin_url}membres/fiche.php?id={$membre.id}">{$membre.identite}</a></li>
        {if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}<li><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>{/if}
        {if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN) && $user.id != $membre.id}
            <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
        {/if}
    </ul>
</nav>

<dl class="cotisation">
    <dt>Activités et cotisations</dt>
    {foreach from=$services item="service"}
    <dd{if $service.archived} class="disabled"{/if}>
        {$service.label}
        {if $service.archived} <em>(activité passée)</em>{/if}
        {if $service.status == -1 && $service.end_date} — expirée
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
            {linkbutton href="!services/user/?id=%d"|args:$membre.id label="Liste des inscriptions aux activités" shape="menu"}
        {/if}
        {if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
            {linkbutton href="!services/user/subscribe.php?user=%d"|args:$membre.id label="Inscrire à une activité" shape="plus"}
        {/if}
    </dd>
    {if count($services)}
    <dd>
        {linkbutton shape="alert" label="Liste des rappels envoyés" href="!services/reminders/user.php?id=%d"|args:$membre.id}
    </dd>
    {/if}
    {if $session->canAccess($session::SECTION_USERS, $session::ACCESS_READ)}
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
		<dd>{$category.name} <span class="permissions">{display_permissions permissions=$category}</span></dd>
		<dt>Inscription</dt>
		<dd>{$membre.date_inscription|date_short}</dd>
		<dt>Dernière connexion</dt>
		<dd>{if empty($membre.date_connexion)}Jamais{else}{$membre.date_connexion|date_short:true}{/if}</dd>
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

{include file="admin/membres/_details.tpl" champs=$champs data=$membre show_message_button=true mode="edit"}

{include file="admin/_foot.tpl"}