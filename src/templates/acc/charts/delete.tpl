{include file="admin/_head.tpl" title="Supprimer un plan comptable" current="acc/charts"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Supprimer ce plan comptable ?</legend>
		<h3 class="warning">
			Êtes-vous sûr de vouloir supprimer le plan comptable
			«&nbsp;{$chart.label}&nbsp;»&nbsp;?
		</h3>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_charts_delete_%d"|args:$chart.id}
		<input type="submit" name="delete" value="Supprimer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}