{include file="_head.tpl" title="Modifier un exercice" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Modifier un exercice</legend>
		<dl>
			{input type="text" label="Libellé" name="label" source=$year required=true}
		</dl>
	</fieldset>
	<fieldset>
		<legend>Dates</legend>
		<dl>
			{input type="date" label="Début de l'exercice" name="start_date" source=$year required=true}
			{input type="date" label="Fin de l'exercice" name="end_date" source=$year required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="edit" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}