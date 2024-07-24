<fieldset>
	<legend>Confirmation</legend>
	<dl>
		{input type="password" name="password_check" label="Mot de passe actuel" help="Entrez votre mot de passe actuel pour confirmer les changements." autocomplete="current-password" required=true}
	</dl>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{button type="submit" name="confirm" label="Confirmer" shape="right" class="main"}
</p>
