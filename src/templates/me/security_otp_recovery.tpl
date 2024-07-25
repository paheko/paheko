{include file="_head.tpl" title="Codes de secours" current="me"}

{include file="./_nav.tpl" current="security"}

{form_errors}

<p class="help">
	Les codes de secours peuvent servir à accéder à votre compte si vous perdez l'accès à votre téléphone, et ne pouvez plus générer de codes à usage unique pour l'authentification à double facteur. Chaque code ne peut être utilisé qu'une seule fois.
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
		{include file="./_security_confirm_password.tpl" name="generate"}
	</form>
{elseif $verified}
	<p class="block alert">
		Conservez ces codes dans un endroit sûr. Ces codes sont le dernier moyen de vous connecter si vous perdez votre second facteur.<br />
		Il est conseillé de les enregistrer dans un gestionnaire de mots de passe, comme KeepassXC par exemple.
	</p>
	{if $user.otp_recovery_codes}
		<p class="otp-recovery">{input type="textarea" copy=true default=$codes name="" readonly=true class="otp" rows=$user.otp_recovery_codes|count cols=6}</p>
		<h3 class="ruler">Générer de nouveaux codes de secours</h3>
		<p class="help">En générant de nouveaux codes, les anciens codes cesseront de fonctionner.</p>
		<p>{linkbutton shape="reload" href="?generate" label="Re-générer les codes de secours"}</p>
	{else}
		<p>{linkbutton shape="reload" href="?generate" label="Générer les codes de secours"}</p>
	{/if}
{else}
	<h3 class="warning">Indiquez votre mot de passe pour accéder aux codes</h3>
	<form method="post" action="{$self_url}" data-focus="1">
		{include file="./_security_confirm_password.tpl" name="verify" help="Entrez votre mot de passe actuel pour voir les codes."}
	</form>
{/if}

{include file="_foot.tpl"}