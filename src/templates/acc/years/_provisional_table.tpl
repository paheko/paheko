
<table class="list">
	<thead>
		<tr>
			{if $edit}
				<th>Compte</th>
				<th class="money">Montant</th>
				<td></td>
			{else}
				<th colspan="2">Compte</th>
				<th class="money">Montant</th>
			{/if}
		</tr>
	</thead>
	<tbody>
		{foreach from=$table item="row"}
		<tr>
			{if $edit}
				<td>
					{input type="list" target="!acc/charts/accounts/selector.php?types=%s&id_year=%d"|args:$type:$year.id name="lines[account][]" default=$row.account_selector}
				</td>
				<td width="20%">{input type="money" name="lines[amount][]" default=$row.amount}</td>
				<td width="5%" class="actions">{button type="button" shape="minus" class="remove" title="Supprimer cette ligne"}</td>
			{else}
				<td>
					{link class="num" href="!acc/accounts/journal.php?id=%d&year=%d"|args:$row.id_account:$year.id label=$row.code}
				</td>
				<th>{$row.label}</th>
				<td class="money">{$row.amount|raw|money}</td>
			{/if}
		</tr>
		{/foreach}
	</tbody>
</table>