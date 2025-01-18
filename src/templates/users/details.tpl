{include file="_head.tpl" title="%s (%s)"|args:$user->name():$category.name current="users"}

{include file="users/_nav_user.tpl" id=$user.id current="details"}

{form_errors}

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
	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
		{if !$user->isHidden()}
			<dd>
				{linkbutton href="!services/user/subscribe.php?user=%d"|args:$user.id label="Inscrire à une activité" shape="plus" target="_dialog" accesskey="V"}
			</dd>
		{else}
			<dd class="help">
				Ce membre est dans une catégorie caché, il n'est plus possible de l'inscrire à une activité.
			</dd>
		{/if}
	{/if}
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
	{if $user->isChild()}
		<dt>Membre responsable</dt>
		<dd>{link href="?id=%d"|args:$user.id_parent label=$parent_name}</dd>
		{if count($siblings)}
			<dt>Autres membres rattachés à {$parent_name}</dt>
			{foreach from=$siblings item="sibling"}
				<dd>{link href="?id=%d"|args:$sibling.id label=$sibling.name}</dd>
			{/foreach}
		{/if}
	{elseif count($children)}
		<dt>Membres rattachés</dt>
		{foreach from=$children item="child"}
			<dd>{link href="?id=%d"|args:$child.id label=$child.name}</dd>
		{/foreach}
	{/if}
</dl>

<aside class="describe">
	<dl class="describe">
		{if $user.date_updated}
			<dt>Fiche modifiée le</dt>
			<dd>{$user.date_updated|date_long:true}</dd>
			<dd>
				{linkbutton shape="history" label="Historique" href="!users/log.php?history=%d"|args:$user.id}
			</dd>
		{/if}
		<dt>Catégorie</dt>
		<dd>{$category.name}</dd>
		{if $user->isHidden()}
			<dd>{tag color="darkred" label="Catégorie cachée"}</dd>
		{/if}
		<dt>Droits</dt>
		<dd><span class="permissions">{display_permissions permissions=$category}</span></dd>
		<dt>Dernière connexion</dt>
		<dd>{if empty($user.date_login)}Jamais{else}{$user.date_login|date_short:true}{/if}</dd>
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
		<dd>
			{linkbutton shape="menu" label="Journal d'audit" href="!users/log.php?id=%d"|args:$user.id}
		</dd>
		{/if}
		<dt>Sécurité</dt>
		<dd>
			{if empty($user.password)}
				{tag color="darkgrey" label="Pas de mot de passe"}
			{else}
				{tag color="darksalmon" label="Mot de passe configuré"}
				{if $user.otp_secret}
					{tag color="darkgreen" label="2FA"}
				{/if}
				{if $user.pgp_key}
					{tag color="olive" label="PGP"}
				{/if}
			{/if}
		</dd>
		{if $can_change_password}
			<dd>
			{if $logged_user.id == $user.id}
				{linkbutton shape="settings" label="Modifier mon mot de passe" href="!me/security.php"}
			{elseif $user.password}
				{linkbutton shape="settings" label="Modifier le mot de passe" href="edit_security.php?id=%d"|args:$user.id target="_dialog"}
			{else}
				{linkbutton shape="settings" label="Définir un mot de passe" href="edit_security.php?id=%d"|args:$user.id target="_dialog"}
			{/if}
			</dd>
		{/if}
		{if $can_login}
		<dd>
			<form method="post" action="" onsubmit="return confirm(&quot;Cela va vous déconnecter et vous reconnecter comme si vous étiez ce membre. Continuer ?&quot);">
				{csrf_field key=$csrf_key}
				{button name="login_as" type="submit" shape="login" label="Se connecter à sa place"}
			</form>
		</dd>
		{/if}
	</dl>
</aside>

{include file="users/_details.tpl" data=$user show_message_button=true context="manage"}

{$snippets|raw}

{include file="_foot.tpl"}