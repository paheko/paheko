{include file="_head.tpl" title="Lier une inscription à une écriture" current="acc/accounts"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Lier à une écriture</legend>

		<dl>
			{input type="number" label="Numéro de l'écriture" name="id_transaction" required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}