{include file="admin/_head.tpl" title="Supprimer un exercice" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Supprimer un exercice</legend>
		<h3 class="warning">
			Êtes-vous sûr de vouloir supprimer l'exercice «&nbsp;{$year.label}&nbsp;» du {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'} ?
		</h3>
		<p class="help">
			Attention, l'exercice ne pourra pas être supprimé si des opérations y sont toujours affectées.
		</p>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_years_delete_%s"|args:$year.id}
		<input type="submit" name="delete" value="Supprimer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}