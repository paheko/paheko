{include file="admin/_head.tpl" title="Projets" current="acc/years"}

<nav class="tabs">
	{if CURRENT_YEAR_ID}
	<aside>
		{linkbutton label="Créer un nouveau projet" href="!acc/projects/new.php" shape="plus"}
	</aside>
	{/if}

	<ul>
		<li><a href="{$admin_url}acc/years/">Exercices</a></li>
		<li class="current"><a href="{$admin_url}acc/projects/">Projets <em>(compta analytique)</em></a></li>
	</ul>

	<aside>
	{if $order_code}
		{linkbutton href="%s?by_year=%d"|args:$self_url_no_qs,$by_year label="Trier les projets par libellé" shape="menu"}
	{else}
		{linkbutton href="%s?by_year=%d&order_code=1"|args:$self_url_no_qs,$by_year label="Trier les projets par code" shape="menu"}
	{/if}
	</aside>

	<ul class="sub">
		<li{if !$by_year} class="current"{/if}><a href="{$self_url_no_qs}?order_code={$order_code}">Par projet</a></li>
		<li{if $by_year} class="current"{/if}><a href="{$self_url_no_qs}?by_year=1&order_code={$order_code}">Par exercice</a></li>
	</ul>
</nav>

<div class="year-header">
	<h2>{$config.nom_asso} — Projets</h2>

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
			<tbody>
				<tr class="title">
					<th colspan="5">
						<h2 class="ruler">{$parent.label}</h2>
						{if $parent.description}<p class="help">{$parent.description|escape|nl2br}</p>{/if}
					</th>
				</tr>
			{foreach from=$parent.items item="item"}
				<tr>
					<th>{$item.label}</th>
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