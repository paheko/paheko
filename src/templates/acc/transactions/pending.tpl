{include file="_head.tpl" title="Dettes et créances non réglées sur les autres exercices" current="acc/simple"}

<nav class="tabs">
	<aside>
		{exportmenu}
		{linkbutton shape="search" href="!acc/search.php" label="Recherche"}
	</aside>
</nav>

{if !$list->count()}
	<p class="alert block">
		Aucune écriture à afficher.
	</p>
{else}
	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="line"}
			<tr>
				<td>{$line.year_label}</td>
				<td>{$line.type_label}</td>
				<td class="num">{link href="!acc/transactions/details.php?id=%d"|args:$line.id label="#%d"|args:$line.id}</td>
				<td>{$line.date|date_short}</td>
				<td class="money">{$line.change|abs|raw|money}</td>
				<td>{$line.reference}</td>
				<th>{$line.label}</th>
				<td class="actions">
					{if $line.type == Entities\Accounting\Transaction::TYPE_DEBT && ($line.status & Entities\Accounting\Transaction::STATUS_WAITING)}
						{linkbutton shape="check" label="Régler cette dette" href="!acc/transactions/new.php?payoff=%d"|args:$line.id}
					{elseif $line.type == Entities\Accounting\Transaction::TYPE_CREDIT && ($line.status & Entities\Accounting\Transaction::STATUS_WAITING)}
						{linkbutton shape="export" label="Régler cette créance" href="!acc/transactions/new.php?payoff=%d"|args:$line.id}
					{/if}

					{linkbutton href="!acc/transactions/details.php?id=%d"|args:$line.id label="Détails" shape="search"}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

	</form>

	{$list->getHTMLPagination()|raw}
{/if}

{include file="_foot.tpl"}