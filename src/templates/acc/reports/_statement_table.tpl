<table class="list">
	{if !empty($caption)}
		<caption><h3>{$caption}</h3></caption>
	{/if}
	<tbody>
	{foreach from=$accounts item="account"}
		<tr class="compte">
			<td class="num">
				{if !empty($year)}<a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$year.id}">{$account.code}</a>
				{else}{$account.code}
				{/if}
			</td>
			<th>{$account.label}</th>
			<td class="money">{$account.sum|raw|money}</td>
		</tr>
	{/foreach}
	</tbody>
</table>