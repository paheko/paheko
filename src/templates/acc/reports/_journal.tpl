<table class="list multi">
	<thead>
		<tr>
			<td class="num">N°</td>
			<td>Pièce comptable</td>
			<td>Date</td>
			<th>Libellé</th>
			{if !empty($with_linked_users)}<td>Membres liés</td>{/if}
			<td>Comptes</td>
			<td class="money">Débit</td>
			<td class="money">Crédit</td>
			<td>Libellé ligne</td>
			<td>Réf. ligne</td>
			{if !empty($action)}<td></td>{/if}
		</tr>
	</thead>
	{foreach from=$journal item="transaction"}
	<tbody>
		<tr>
			<td rowspan="{$transaction.lines|count}" class="num">{if $transaction.id}<a href="{$admin_url}acc/transactions/details.php?id={$transaction.id}">#{$transaction.id}</a>{/if}</td>
			<td rowspan="{$transaction.lines|count}">{$transaction.reference}</td>
			<td rowspan="{$transaction.lines|count}">{$transaction.date|date_short}</td>
			<th rowspan="{$transaction.lines|count}">{$transaction.label}</th>
			{if !empty($with_linked_users)}<td rowspan="{$transaction.lines|count}">{$transaction.linked_users}</td>{/if}
		{foreach from=$transaction.lines key="k" item="line"}
			<td>{$line.account_code} - {$line.account_label}</td>
			<td class="money">{$line.debit|raw|money}</td>
			<td class="money">{$line.credit|raw|money}</td>
			<td>{$line.label}</td>
			<td>{$line.reference}</td>
			{if !empty($action) && $k == 0}
			<td class="actions" rowspan="{$transaction.lines|count}">
				{linkbutton href=$action.href|args:$transaction.id shape=$action.shape label=$action.label}
			</td>
			{/if}
		</tr>
		<tr>
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>