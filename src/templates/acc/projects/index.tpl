{include file="admin/_head.tpl" title="Projets" current="acc/years"}

{include file="./_nav.tpl" current=$current}

<div class="year-header">
	<h2>{$config.nom_asso} — Projets</h2>

	<p class="noprint">
		{if $by_year}
			{linkbutton href="?order_code=%s"|args:$order_code label="Grouper par projet" shape="left"}
		{else}
			{linkbutton href="?by_year=1&order_code=%s"|args:$order_code label="Grouper par exercice" shape="right"}
		{/if}
		{if $order_code}
			{linkbutton href="%s?by_year=%d"|args:$self_url_no_qs,$by_year label="Trier les projets par libellé" shape="up"}
		{else}
			{linkbutton href="%s?by_year=%d&order_code=1"|args:$self_url_no_qs,$by_year label="Trier les projets par code" shape="down"}
		{/if}
	</p>


	<p class="noprint print-btn">
		<button onclick="window.print(); return false;" class="icn-btn" data-icon="⎙">Imprimer</button>
	</p>
</div>

{if !empty($list)}


	<table class="list projects">
		<thead>
			<tr>
				<td>Année</td>
				<td></td>
				<td class="money">Charges</td>
				<td class="money">Produits</td>
				<td class="money">Débits</td>
				<td class="money">Crédits</td>
				<td class="money">Solde</td>
			</tr>
		</thead>
		{foreach from=$list item="parent"}
			<tbody{if $parent.archived} class="archived"{/if}>
				<tr class="title">
					<th colspan="7">
						<h2 class="ruler">{$parent.label}{if $parent.archived} <em>(archivé)</em>{/if}</h2>
						{if $parent.description}<p class="help">{$parent.description|escape|nl2br}</p>{/if}
					{if !$by_year && $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
					<p class="actions">
						{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$parent.id target="_dialog"}
						{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$parent.id target="_dialog"}
					</p>
					{/if}
					</th>
				</tr>
			{foreach from=$parent.items item="item"}
				<tr class="{if $item.label == 'Total'}total{/if} {if $item.archived}archived{/if}">
					<th>{$item.label}{if $item.archived} <em>(archivé)</em>{/if}</th>
					<td>
					<span class="noprint">
						<a href="{$admin_url}acc/reports/graphs.php?project={$item.id_project}&amp;year={$item.id_year}">Graphiques</a>
						| <a href="{$admin_url}acc/reports/trial_balance.php?project={$item.id_project}&amp;year={$item.id_year}">Balance générale</a>
						| <a href="{$admin_url}acc/reports/journal.php?project={$item.id_project}&amp;year={$item.id_year}">Journal général</a>
						| <a href="{$admin_url}acc/reports/ledger.php?project={$item.id_project}&amp;year={$item.id_year}">Grand livre</a>
						| <a href="{$admin_url}acc/reports/statement.php?project={$item.id_project}&amp;year={$item.id_year}">Compte de résultat</a>
						| <a href="{$admin_url}acc/reports/balance_sheet.php?project={$item.id_project}&amp;year={$item.id_year}">Bilan</a>
						{if $item.total && $by_year}
						| <a href="{$admin_url}acc/reports/ledger.php?projects_only=1&amp;year={$item.id_year}">Grand livre analytique</a>
						{/if}
					</span>
					</td>
					<td class="money">{$item.sum_expense|raw|money}</td>
					<td class="money">{$item.sum_revenue|raw|money}</td>
					<td class="money">{$item.debit|raw|money:false}</td>
					<td class="money">{$item.credit|raw|money:false}</td>
					<td class="money">{$item.sum|raw|money:false}</td>
				</tr>
			{/foreach}
			</tbody>
		{/foreach}
	</table>

{else}
	<p class="block alert">
		Il n'y a pas de projet visible en cours.
		{if !$projects_count}
			{linkbutton label="Créer un nouveau projet" href="new.php" shape="plus"}
		{else}
			Le solde des projets apparaîtra quand des écritures seront affectées à ces projets.
		{/if}
	</p>
{/if}

{include file="admin/_foot.tpl"}