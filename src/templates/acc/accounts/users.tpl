{include file="admin/_head.tpl" title="Comptes de membres" current="acc/accounts"}

{include file="acc/_year_select.tpl"}

{include file="acc/accounts/_nav.tpl" current="users"}


<p class="help">
	Ce tableau présente une liste de comptes «&nbsp;virtuels&nbsp;» représentant les membres liés aux écritures.
	Seules les écritures liées à des comptes de tiers (en favori) sont comptabilisées.<br />
	Les membres qui n'ont aucune écriture associée n'apparaissent pas dans ce tableau.
</p>

	{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<td class="num"><a href="{$admin_url}acc/transactions/user.php?id={$row.id}&amp;year={$current_year.id}">{$row.user_number}</a></td>
			<th><a href="{$admin_url}acc/transactions/user.php?id={$row.id}&amp;year={$current_year.id}">{$row.user_identity}</a></th>
			<td class="money">
				{if $row.balance < 0}<strong class="error">{/if}
				{$row.balance|raw|money_currency:false}
				{if $row.balance < 0}</strong>{/if}
			</td>
			<td>
				<em class="alert">
					{if $row.balance < 0}Dette
					{elseif $row.balance > 0}Créance
					{/if}
				</em>
			</td>
			<td class="actions">
				{linkbutton label="Journal" shape="menu" href="!acc/transactions/user.php.php?id=%d&year=%d"|args:$row.id,$current_year.id}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>


{include file="admin/_foot.tpl"}