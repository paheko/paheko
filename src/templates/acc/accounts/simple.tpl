{include file="admin/_head.tpl" title="Suivi : %s"|args:$types[$type] current="acc/simple"}

{if empty($year)}
	{include file="acc/_year_select.tpl"}
{else}
	<nav class="acc-year">
		<h4>Exercice sélectionné&nbsp;:</h4>
		<h3>{$year.label} — {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}</h3>
	</nav>
{/if}

{if Entities\Accounting\Account::isReversed($type)}
	{include file="acc/_simple_help.tpl" link=null type=$type}
{/if}

<nav class="tabs">
	<aside>
	{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
		{linkbutton href="%s?type=%d&export=csv"|args:$self_url_no_qs,$type label="Export CSV" shape="export"}
		{linkbutton href="%s?type=%d&export=ods"|args:$self_url_no_qs,$type label="Export tableur" shape="export"}
	{/if}
	</aside>
	<ul>
		{foreach from=$types key="key" item="label"}
		<li{if $type == $key} class="current"{/if}><a href="?type={$key}">{$label}</a></li>
		{/foreach}
	</ul>
</nav>

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="line"}
		<tr>
			<td class="num"><a href="journal.php?id={$line.id_account}">{$line.account}</a></td>
			<td>{$line.account_label}</td>
			<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
			<td>{$line.date|date_fr:'d/m/Y'}</td>
			<td class="money">{if $line.change > 0}+{else}-{/if}{$line.change|abs|raw|html_money}</td>
			<td>{$line.reference}</td>
			<th>{$line.label}</th>
			<td>{$line.line_reference}</td>
			<td class="actions">
				{if $line.type == Entities\Accounting\Transaction::TYPE_DEBT}
					{linkbutton shape="check" label="Régler cette dette" href="acc/transactions/new.php?payoff_for=%d"|args:$line.id}
				{elseif $line.type == Entities\Accounting\Transaction::TYPE_CREDIT}
					{linkbutton shape="export" label="Régler cette créance" href="acc/transactions/new.php?payoff_for=%d"|args:$line.id}
				{/if}

				{linkbutton href="acc/transactions/details.php?id=%d"|args:$line.id label="Détails" shape="search"}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}

{include file="admin/_foot.tpl"}