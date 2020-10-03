{include file="admin/_head.tpl" title="Commencer un exercice" current="acc/years" js=1}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Commencer un nouvel exercice</legend>
		<dl>
			{input type="select_groups" options=$charts name="id_chart" label="Plan comptable" required=true}
			{input type="text" name="label" label="Libellé" required=true}
			{input type="date" label="Début de l'exercice" name="start_date" required=true default=$start_date}
			{input type="date" label="Fin de l'exercice" name="end_date" required=true default=$end_date}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_years_new"}
		<input type="submit" name="new" value="Créer ce nouvel exercice &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}