{include file="admin/_head.tpl" title="%s (%s)"|args:$user->name():$category.name current="users"}

<nav class="tabs">
	<aside>
	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
		{linkbutton href="edit.php?id=%d"|args:$user.id shape="edit" label="Modifier"}
	{/if}
	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN) && $logged_user.id != $user.id}
		{linkbutton href="delete.php?id=%d"|args:$user.id shape="delete" label="Supprimer" target="_dialog"}
	{/if}
	</aside>
	<ul>
		<li class="current">{link href="!users/details.php?id=%d"|args:$user.id label="Fiche membre"}</li>
		<li>{link href="!services/user/?id=%d"|args:$user.id label="Inscriptions aux activités"}</li>
		<li>{link href="!services/reminders/user.php?id=%d"|args:$user.id label="Rappels envoyés"}</li>
	</ul>
</nav>

<dl class="cotisation">
	<dt>Activités et cotisations</dt>
	{foreach from=$services item="service"}
	<dd{if $service.archived} class="disabled"{/if}>
		{$service.label}
        {if $service.archived} <em>(activité passée)</em>{/if}
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
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
			{linkbutton href="!services/user/subscribe.php?user=%d"|args:$user.id label="Inscrire à une activité" shape="plus"}
		{/if}
	</dd>
	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_READ)}
		{if !empty($transactions_linked)}
			<dt>Écritures comptables liées</dt>
			<dd><a href="{$admin_url}acc/transactions/user.php?id={$user.id}">{$transactions_linked} écritures comptables liées à ce membre</a></dd>
		{/if}
		{if !empty($transactions_created)}
			<dt>Écritures comptables créées</dt>
			<dd><a href="{$admin_url}acc/transactions/creator.php?id={$user.id}">{$transactions_created} écritures comptables créées par ce membre</a></dd>
		{/if}
	{/if}
</dl>

<aside class="describe">
	<dl class="describe">
		<dt>Catégorie</dt>
		<dd>{$category.name} <span class="permissions">{display_permissions permissions=$category}</span></dd>
		<dt>Dernière connexion</dt>
		<dd>{if empty($user.date_login)}Jamais{else}{$user.date_login|date_short:true}{/if}</dd>
		<dt>Mot de passe</dt>
		<dd>
			{if empty($user.password)}
				Pas de mot de passe configuré
			{else}
				<b class="icn">☑</b> Oui
				{if !empty($user.otp_secret)}
					(<b class="icn">🔒</b> avec second facteur)
				{else}
					(<b class="icn">🔓</b> sans second facteur)
				{/if}
		{/if}
		</dd>
	</dl>
</aside>

{include file="users/_details.tpl" data=$user show_message_button=true mode="edit"}

{include file="admin/_foot.tpl"}