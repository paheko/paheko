{include file="_head.tpl" title="Réouvrir un exercice clôturé" current="config"}

{form_errors}

<form method="post" action="">
	<fieldset>
		<legend>Déverrouiller un exercice</legend>
		<h3 class="warning">Déverrouiller l'exercice « {$year.label} » ?</h3>
	</fieldset>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="unlock" label="Déverrouiller cet exercice" shape="unlock" class="main"}
	</p>
</form>

{include file="_foot.tpl"}