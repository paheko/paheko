{include file="_head.tpl" title="Comptes de membres" current="acc/accounts"}

{include file="acc/_year_select.tpl"}

{include file="acc/accounts/_nav.tpl" current="users"}

<p class="actions">
	{if $all}
		{linkbutton shape="eye-off" label="Seulement les membres qui ont une dette ou une créance" href="?all=0"}
	{else}
		{linkbutton shape="eye" label="Voir tous les membres" href="?"}
	{/if}
</p>

<p class="help">
	Ce tableau présente une liste de comptes «&nbsp;virtuels&nbsp;» représentant les membres liés aux écritures.<br />
	Les membres qui n'ont aucune écriture associée n'apparaissent pas dans ce tableau.
</p>


{if !$list->count()}
<p class="alert block">Aucune écriture liée à un membre n'existe sur cet exercice.</p>
{else}
	{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<td class="num"><a href="{$admin_url}acc/transactions/user.php?id={$row.id}&amp;year={$current_year.id}">{$row.user_number}</a></td>
			<th><a href="{$admin_url}acc/transactions/user.php?id={$row.id}&amp;year={$current_year.id}">{$row.user_identity}</a></th>
			{if $all}
			<td class="money">
				{$row.products|raw|money_currency:false}
			</td>
			<td class="money">
				{$row.expenses|raw|money_currency:false}
			</td>
			{/if}
			<td class="money">
				{$row.balance|raw|money_currency:false}
			</td>
			<td>
				{if $row.balance < 0}{tag preset="debt"}
				{elseif $row.balance > 0}{tag preset="credit"}
				{/if}
			</td>
			<td class="actions">
				{linkbutton label="Journal" shape="menu" href="!acc/transactions/user.php?id=%d&year=%d"|args:$row.id,$current_year.id}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>
{/if}

<p class="help">
	Dette = l'association doit de l'argent à ce membre<br />
	Créance = le membre doit de l'argent à l'association
</p>


{include file="_foot.tpl"}