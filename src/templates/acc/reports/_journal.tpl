<table class="list multi statement autofilter">
	<thead>
		<tr>
			<td class="num">N°</td>
			<td>Pièce comptable</td>
			<td>Date</td>
			<td>Libellé</td>
			{if !empty($with_linked_users)}<td>Membres associés</td>{/if}
			<td>Comptes</td>
			<td class="money">Débit</td>
			<td class="money">Crédit</td>
			<td>Libellé ligne</td>
			<td>Réf. ligne</td>
			{if !empty($has_projects)}
			<td>Projet</td>
			{/if}
			{if isset($criterias) && $criterias.project}<td>Cumul</td>{/if}
			{if !empty($action)}<td></td>{/if}
		</tr>
	</thead>
	{foreach from=$journal item="transaction"}
	<tbody>
		<tr>
			<td rowspan="{$transaction.lines|count}" class="num" data-spreadsheet-type="string">{if $transaction.id}<a href="{$admin_url}acc/transactions/details.php?id={$transaction.id}">#{$transaction.id}</a>{/if}</td>
			<td rowspan="{$transaction.lines|count}" data-spreadsheet-type="string">{$transaction.reference}</td>
			<td rowspan="{$transaction.lines|count}" data-spreadsheet-type="date" data-spreadsheet-value="{$transaction.date|date:'Y-m-d'}">{$transaction.date|date_short}</td>
			<th scope="row" rowspan="{$transaction.lines|count}" data-spreadsheet-type="string">{$transaction.label}</th>
			{if !empty($with_linked_users)}<td rowspan="{$transaction.lines|count}">{$transaction.linked_users}</td>{/if}
		{foreach from=$transaction.lines key="k" item="line"}
			{if $k > 0}<tr>{/if}
			<td data-spreadsheet-type="string">{$line.account_code} - {$line.account_label}</td>
			<td class="money">{$line.debit|raw|money}</td>
			<td class="money">{$line.credit|raw|money}</td>
			<td data-spreadsheet-type="string">{$line.label}</td>
			<td data-spreadsheet-type="string">{$line.reference}</td>
			{if !empty($has_projects)}
			<td data-spreadsheet-type="string" class="num">{if $line.id_project}{link href="!acc/reports/statement.php?project=%d&year=%d"|args:$line.id_project:$line.id_year label=$line.project_code|truncate:10}{/if}</td>
			{/if}
			{if isset($criterias) && $criterias.project}
				<?php $running_sum = ($running_sum ?? 0) - $line->debit + $line->credit; ?>
				<td>{$running_sum|raw|money:false}</td>
			{/if}
			{if !empty($action) && $k == 0}
			<td class="actions" rowspan="{$transaction.lines|count}">
				{linkbutton href=$action.href|args:$transaction.id shape=$action.shape label=$action.label}
			</td>
			{/if}
		</tr>
		{/foreach}
	</tbody>
	{/foreach}
</table>