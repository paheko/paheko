{include file="admin/_head.tpl" title="Clôturer un exercice" current="acc/years"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Clôturer un exercice</legend>
		<h3 class="warning">
			Êtes-vous sûr de vouloir clôturer l'exercice «&nbsp;{$year.label}&nbsp;» ?
		</h3>
		<p class="block alert">
			Un exercice clôturé ne peut plus être rouvert ou modifié&nbsp;!<br />
			Il ne sera plus possible de modifier ou supprimer les écritures de l'exercice clôturé.
		</p>
		<dl>
			<dt>Début de l'exercice</dt>
			<dd>{$year.start_date|date_short}</dd>
			<dt>Fin de l'exercice</dt>
			<dd>{$year.end_date|date_short}</dd>
			<dd class="help">Si la date de clôture ne convient pas, il est possible de <a href="edit.php?id={$year.id}">modifier l'exercice</a> préalablement à la clôture.</dd>
		</h3>
	</fieldset>

	<p class="help">Les soldes créditeurs ou débiteurs de chaque compte pourront être reportés automatiquement lors de l'ouverture de l'exercice suivant.</p>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="close" label="Clôturer" shape="lock" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}