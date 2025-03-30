	<table class="list projects">
		{if !empty($caption)}<caption>{$caption}</caption>{/if}
		<thead>
			<tr>
				<td>Projet</td>
				<td></td>
				<td class="money">Charges</td>
				<td class="money">Produits</td>
				<td class="money">Résultat</td>
				<td class="money">Débits</td>
				<td class="money">Crédits</td>
				<td class="money">Solde</td>
			</tr>
		</thead>
		{foreach from=$list item="parent"}
			<tbody{if $parent.archived} class="archived"{/if}>
				<tr class="title">
					<td colspan="4">
						<h2 class="ruler-left">{$parent.label}{if $parent.archived} <em>(archivé)</em>{/if}</h2>
						{if $parent.description}<p class="help">{$parent.description|escape|nl2br}</p>{/if}
					</td>
					<td colspan="4" class="actions">
					{if !$table_export && !$by_year && $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
						{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$parent.id target="_dialog"}
						{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$parent.id target="_dialog"}
					{/if}
					{if !$table_export && $by_year}
						{linkbutton href="!acc/reports/ledger.php?project=all&year=%d"|args:$parent.id_year label="Grand livre analytique"}
					{/if}
					</td>
				</tr>
			{foreach from=$parent.items item="item"}
				<?php $result = $item->sum_revenue - $item->sum_expense; ?>
				<tr class="{if $item.label == 'Total'}total{/if} {if $item.archived}archived{/if}">
					<th>{$item.label}{if $item.archived} <em>(archivé)</em>{/if}</th>
					<td>
					{if !$table_export}
					<?php
					$id_project = $item->id_project ?: 'all';
					?>
					<span class="noprint">
						<a href="{$admin_url}acc/reports/graphs.php?project={$id_project}&amp;year={$item.id_year}">Graphiques</a>
						| <a href="{$admin_url}acc/reports/trial_balance.php?project={$id_project}&amp;year={$item.id_year}">Balance générale</a>
						| <a href="{$admin_url}acc/reports/journal.php?project={$id_project}&amp;year={$item.id_year}">Journal général</a>
						| <a href="{$admin_url}acc/reports/ledger.php?project={$id_project}&amp;year={$item.id_year}">Grand livre</a>
						| <a href="{$admin_url}acc/reports/statement.php?project={$id_project}&amp;year={$item.id_year}">Compte de résultat</a>
						| <a href="{$admin_url}acc/reports/balance_sheet.php?project={$id_project}&amp;year={$item.id_year}">Bilan</a>
					</span>
					{/if}
					</td>
					<td class="money">{$item.sum_expense|raw|money}</td>
					<td class="money">{$item.sum_revenue|raw|money}</td>
					<td class="money">{$result|raw|money:true:true}</td>
					<td class="money">{$item.debit|raw|money:false}</td>
					<td class="money">{$item.credit|raw|money:false}</td>
					<td class="money">{$item.sum|raw|money:false}</td>
				</tr>
			{/foreach}
			</tbody>
		{/foreach}
	</table>
