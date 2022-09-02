{include file="_head.tpl" title="Accès document" current=null layout="public"}

{if $has_password}
<p class="block error">
	Le mot de passe fourni ne correspond pas.<br />Merci de vérifier la saisie.
</p>
{else}
	<p class="block alert">Un mot de passe est nécessaire pour accéder à ce document.</p>
{/if}

<form method="post" action="">
	<fieldset>
		<legend>Accès au document</legend>
		<dl>
			{input type="password" name="p" required=true label="Mot de passe"}
		</dl>
		<p class="submit">
			{button type="submit" label="Accéder au document" shape="right" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}