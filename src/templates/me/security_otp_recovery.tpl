{include file="_head.tpl" title="Codes de secours" current="me"}

{include file="./_nav.tpl" current="security"}

{form_errors}

<p class="help">
	Les codes de secours peuvent servir à accéder à votre compte si vous perdez l'accès à votre téléphone, et ne pouvez plus générer de codes à usage unique pour l'authentification à double facteur.
</p>

{if $generate}
	<h3 class="warning">
		{if $user.otp_recovery_codes}
			Effacer les codes de secours existants et en créer des nouveaux ?
		{else}
			Confirmez la création de codes de secours
		{/if}
	</h3>

	<form method="post" action="{$self_url}" data-focus="1">
		{include file="./_security_confirm_password.tpl"}
	</form>
{else}
	<p class="block alert">
		Conservez ces codes dans un endroit sûr. Ces codes sont le dernier moyen de vous connecter si vous perdez votre second facteur.<br />
		Il est conseillé de les enregistrer dans un gestionnaire de mots de passe, comme KeepassXC par exemple.
	</p>
	{if $user.otp_recovery_codes}
		<pre>{foreach from=$user.otp_recovery_codes item="code"}{$code}<br/>{/foreach}</pre>
	{else}
		<p>{linkbutton shape="reload" href="?generate" label="Générer les codes de secours"}</p>
	{/if}
{/if}

{include file="_foot.tpl"}