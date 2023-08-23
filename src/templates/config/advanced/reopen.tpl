{include file="_head.tpl" title="Réouvrir un exercice clôturé" current="config" custom_css=["config.css"]}

{include file="config/_menu.tpl" current="advanced"}

{form_errors}


{if count($closed_years)}
	<form method="post" action="{$self_url_no_qs}">
		<fieldset>
			<legend>Réouvrir un exercice clôturé</legend>
			<p class="alert block">
				L'exercice sera réouvert, mais une écriture sera ajoutée au journal général indiquant que celui-ci a été réouvert après clôture. Cette écriture ne peut pas être supprimée.
			</p>
			<dl>
				{input type="select" options=$closed_years label="Exercicer à réouvrir" name="year" required=true default_empty="Sélectionner un exercice"}
			</dl>
		</fieldset>
		<p class="submit">
			{csrf_field key="reopen_year"}
			{button type="submit" name="reopen_ok" label="Réouvrir l'exercice sélectionné" shape="reset" class="main"}
		</p>
	</form>
{else}
	<p class="alert block">Il n'y a aucun exercice clôturé. Il est donc impossible d'en réouvrir un :-)</p>
{/if}


{include file="_foot.tpl"}