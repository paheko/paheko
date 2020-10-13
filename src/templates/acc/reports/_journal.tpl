<table class="list multi">
	<thead>
		<tr>
			<td>Réf.</td>
			<td>Date</td>
			<th>Libellé</th>
			<td>Comptes</td>
			<td class="money">Débit</td>
			<td class="money">Crédit</td>
			<td>Libellé ligne</td>
			<td>Réf. ligne</td>
		</tr>
	</thead>
	{foreach from=$journal item="transaction"}
	<tbody>
		<tr>
			<td rowspan="{$transaction.lines|count}" class="num"><a href="{$admin_url}acc/transactions/details.php?id={$transaction.id}">{if $transaction.reference}{$transaction.reference}{else}#{$transaction.id}{/if}</a></td>
			<td rowspan="{$transaction.lines|count}">{$transaction.date|date_fr:'d/m/Y'}</td>
			<th rowspan="{$transaction.lines|count}">{$transaction.label}</th>
		{foreach from=$transaction.lines item="line"}
			<td>{$line.account_code} - {$line.account_label}</td>
			<td class="money">{$line.debit|raw|html_money}</td>
			<td class="money">{$line.credit|raw|html_money}</td>
			<td>{$line.label}</td>
			<td>{$line.reference}</td>
		</tr>
		<tr>
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>