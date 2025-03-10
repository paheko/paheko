{include file="_head.tpl" title="Réouvrir un exercice clôturé" current="config"}

{form_errors}

<form method="post" action="">
	<fieldset>
		<legend>Verrouiller un exercice</legend>
		<h3 class="warning">Verrouiller l'exercice « {$year.label} » ?</h3>
		<p class="alert block">L'exercice ne pourra plus être modifié, aucune écriture ne pourra être modifiée ou supprimée. Mais il pourra être déverrouillé à tout moment.</p>
		<p class="help">Le verrouillage de préparer la clôture de l'exercice, en s'assurant qu'il n'est pas modifié par erreur si on travaille sur plusieurs exercices en même temps.</p>
	</fieldset>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="lock" label="Verrouiller cet exercice" shape="lock" class="main"}
	</p>
</form>

{include file="_foot.tpl"}