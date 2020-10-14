<table class="list">
	{if !empty($caption)}
		<caption><h3>{$caption}</h3></caption>
	{/if}
	<tbody>
	{foreach from=$accounts item="account"}
		<tr class="compte">
			<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$year.id}">{$account.code}</a></td>
			<th>{$account.label}</th>
			<td class="money">{if $abs}{$account.sum|abs|raw|html_money}{else}{$account.sum|raw|html_money}{/if}</td>
		</tr>
	{/foreach}
	</tbody>
</table>