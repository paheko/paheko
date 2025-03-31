{include file="_head.tpl" title="Changer d'exercice" current="acc/years"}

{if $_GET.msg === 'CLOSED'}
	<div class="alert block">
	{if $current_year->isClosed()}
		<h3>L'exercice actuellement sélectionné est clôturé</h3>
	{elseif $current_year->isLocked()}
		<h3>L'exercice actuellement sélectionné est verrouillé</h3>
	{/if}
		<p>Il n'est pas possible d'y ajouter d'écriture.</p>
		<p>Sélectionnez un exercice ouvert pour ajouter une écriture.</p>
	</div>
{/if}

<form method="post" action="{$self_url}" data-focus="1">
	<table class="list">
		{foreach from=$years item="year"}
		<tr{if $current_year && $current_year.id === $year.id} class="checked"{/if}>
			<td>{tag preset=$year->getStatusTagPreset()}</td>
			<th><h3>{$year.label}</h3></th>
			<td>{$year.start_date|date_short} au {$year.end_date|date_short}</td>
			<td class="actions">
				{button type="submit" shape="right" label="Sélectionner" name="switch" value=$year.id}
			</td>
		</tr>
		{/foreach}
	</table>
	{csrf_field key=$csrf_key}
	<input type="hidden" name="from" value="{$from}" />
</form>

{include file="_foot.tpl"}