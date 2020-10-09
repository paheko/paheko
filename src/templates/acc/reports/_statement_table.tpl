<table class="list">
	{if !empty($caption)}
		<caption><h3>{$caption}</h3></caption>
	{/if}
	<tbody>
	{foreach from=$accounts item="account"}
		<tr class="compte">
			<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$year.id}">{$account.code}</a></td>
			<th>{$account.label}</th>
			<td class="money">{$account.sum|abs|escape|html_money}</td>
		</tr>
	{/foreach}
	</tbody>
</table>