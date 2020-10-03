{include file="admin/_head.tpl" title="Changer d'exercice" current="acc/years"}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Changer l'exercice de travail</legend>
		<dl>
			<dd>
				<select name="year">
					{foreach from=$list item="year"}
					<option value="{$year.id}">{$year.label} — {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}</option>
					{/foreach}
				</select>
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_select_year"}
		<input type="hidden" name="from" value="{$from}" />
		<input type="submit" name="change" value="Changer &rarr;" />
	</p>
</form>

{include file="admin/_foot.tpl"}