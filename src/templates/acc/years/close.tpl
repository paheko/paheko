{include file="admin/_head.tpl" title="Clôturer un exercice" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Clôturer un exercice</legend>
		<h3 class="warning">
			Êtes-vous sûr de vouloir clôturer l'exercice «&nbsp;{$year.label}&nbsp;» ?
		</h3>
		<p class="alert">
			Un exercice clôturé ne peut plus être rouvert ou modifié&nbsp;!<br />
			Il ne sera plus possible de modifier ou supprimer les écritures de l'exercice clôturé.
		</p>
		<dl>
			<dt>Début de l'exercice</dt>
			<dd>{$year.start_date|date_fr:'d/m/Y'}</dd>
			<dt>Fin de l'exercice</dt>
			<dd>{$year.end_date|date_fr:'d/m/Y'}</dd>
		</h3>
	</fieldset>

	<p class="help">Les soldes créditeurs ou débiteurs de chaque compte pourront être reportés automatiquement lors de l'ouverture de l'exercice suivant.</p>

	<p class="submit">
		{csrf_field key="acc_years_close_%d"|args:$year.id}
		<input type="submit" name="close" value="Clôturer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}