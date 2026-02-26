{include file="_head.tpl" title="Réouvrir un exercice clôturé" current="config"}

{form_errors}

<form method="post" action="">
	<fieldset>
		<legend>Réouvrir un exercice clôturé</legend>
		<h3 class="warning">Ré-ouvrir l'exercice « {$year.label} » ?</h3>
		<p class="alert block">
			L'exercice sera réouvert, mais une écriture sera ajoutée au journal général indiquant que celui-ci a été réouvert après clôture. Cette écriture ne peut pas être supprimée.
		</p>
		<p class="help">Cette opération est sensée être exceptionnelle, et inhabituelle.</p>
		<dl>
			{input type="checkbox" name="confirm" label="Je confirme la réouverture de cet exercice" value=1}
		</dl>
	</fieldset>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="reopen" label="Réouvrir cet exercice" shape="reset" class="main"}
	</p>
</form>

{include file="_foot.tpl"}