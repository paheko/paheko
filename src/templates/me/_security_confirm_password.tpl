{if !isset($help)}
	{assign var="help" value="Entrez votre mot de passe actuel pour confirmer les changements."}
{/if}

{if !isset($name)}
	{assign var="name" value="confirm"}
{/if}

<fieldset>
	<legend>Confirmation</legend>
	{if isset($warning)}
		<h3 class="warning">{$warning}</h3>
	{/if}
	<dl>
		{input type="password" name="password_check" label="Mot de passe actuel" help=$help autocomplete="current-password" required=true}
	</dl>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{button type="submit" name=$name label="Confirmer" shape="right" class="main"}
</p>
