{include file="admin/_head.tpl" title="Modifier un exercice" current="acc/years" js=1}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Modifier un exercice</legend>
		<dl>
			{input type="text" label="Libellé" name="label" source=$year required=true}
			{input type="date" label="Début de l'exercice" name="start_date" source=$year required=true}
			{input type="date" label="Fin de l'exercice" name="end_date" source=$year required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_years_edit_%s"|args:$year.id}
		<input type="submit" name="edit" value="Enregistrer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}