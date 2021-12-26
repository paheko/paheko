<table class="list">
	{if !empty($caption)}
		<caption><h3>{$caption}</h3></caption>
	{/if}
	{if !empty($year2)}
		<thead>
			<tr>
				<td></td>
				<th></th>
				<td class="money" width="10%">{$year->label_years()}</td>
				<td class="money" width="10%">{$year2->label_years()}</td>
				<td class="money" width="10%">Écart</td>
			</tr>
		</thead>
	{/if}
	<tbody>
	{foreach from=$accounts item="account"}
		<tr class="compte{if isset($year2) && !$account.sum} disabled{/if}">
			<td class="num">
				{if !empty($year) && $account.id}<a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$year.id}">{$account.code}</a>
				{else}{$account.code}
				{/if}
			</td>
			<th>{$account.label}</th>
			<td class="money">{$account.sum|raw|money:false}</td>
			{if isset($year2)}
				<td class="money">{$account.sum2|raw|money:false}</td>
				<td class="money">{$account.change|raw|money:true:true}</td>
			{/if}
		</tr>
	{/foreach}
	</tbody>
</table>