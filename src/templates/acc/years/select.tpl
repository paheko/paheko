{include file="admin/_head.tpl" title="Changer d'exercice" current="acc/years"}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Changer l'exercice de travail</legend>
		<dl>
			<dd>
				<select name="year">
					{foreach from=$list item="year"}
					<option value="{$year.id}">{$year.label} — {$year.start_date|date_short} au {$year.end_date|date_short}</option>
					{/foreach}
				</select>
			</dd>
			<dd class="help">Ici ne peuvent être sélectionnés que les exercices ouverts, car il n'est pas possible de modifier un exercice clos.
				Pour consulter les rapports pour les exercices clos, voir <a href="{$www_url}admin/acc/years/">la liste des exercices</a>.</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_select_year"}
		<input type="hidden" name="from" value="{$from}" />
		{button type="submit" name="change" label="Changer" shape="right" class="main"}
	</p>
</form>

{include file="admin/_foot.tpl"}