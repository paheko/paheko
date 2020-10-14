{include file="admin/_head.tpl" title="Modifier un plan comptable" current="acc/charts"}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Modifier un plan comptable</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=1 source=$chart}
			{input type="select" name="country" label="Pays" required=1 options=$country_list source=$chart}
			<dt><label for="f_archived_1">Archivage</label></dt>
			{input type="checkbox" name="archived" value="1" source=$chart label="Plan comptable archivé" help="Ce plan comptable ne pourra plus être modifié"}
		</dl>
		<p class="submit">
			{csrf_field key="acc_charts_edit_%d"|args:$chart.id}
			<input type="submit" name="save" value="Enregistrer &rarr;" />
		</p>
	</fieldset>
</form>

{include file="admin/_foot.tpl"}