{include file="_head.tpl" title="Verrouiller une écriture" current="acc"}

<form method="post" action="{$self_url}" data-focus="1">
	{form_errors}

	<fieldset>
		<legend>Verrouiller une écriture</legend>
		<p class="help">
			Le verrouillage (aussi appelé validation) d'écritures permet d'empêcher leur modification.
		</p>
		<p class="alert block">
			Attention&nbsp;: une fois verrouillée, l'écriture ne pourra plus être modifiée ni supprimée.
		</p>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="lock" label="Verrouiller" shape="right" class="main"}
	</p>

</form>


{include file="_foot.tpl"}