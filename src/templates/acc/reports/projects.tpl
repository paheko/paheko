{include file="admin/_head.tpl" title="Projets" current="acc/years"}

<nav class="tabs">
	{if CURRENT_YEAR_ID}
	<aside>
		{linkbutton label="Créer un nouveau compte de projet" href="!acc/charts/accounts/new.php?id=%d&type=%d"|args:$current_year.id_chart,$analytical_type shape="plus"}
	</aside>
	{/if}

	<ul>
		<li><a href="{$admin_url}acc/years/">Exercices</a></li>
		{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
		<li><a href="{$admin_url}acc/years/new.php">Nouvel exercice</a></li>
		{/if}
		<li class="current"><a href="{$admin_url}acc/reports/projects.php">Projets <em>(compta analytique)</em></a></li>
	</ul>

	<ul class="sub">
		<li{if !$by_year} class="current"{/if}><a href="{$self_url_no_qs}">Par projet</a></li>
		<li{if $by_year} class="current"{/if}><a href="{$self_url_no_qs}?by_year=1">Par exercice</a></li>
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
						<a href="{$admin_url}acc/reports/graphs.php?analytical={$item.id_account}&year={$item.id_year}">Graphiques</a>
						| <a href="{$admin_url}acc/reports/trial_balance.php?analytical={$item.id_account}&year={$item.id_year}">Balance générale</a>
						| <a href="{$admin_url}acc/reports/journal.php?analytical={$item.id_account}&year={$item.id_year}">Journal général</a>
						| <a href="{$admin_url}acc/reports/ledger.php?analytical={$item.id_account}&year={$item.id_year}">Grand livre</a>
						| <a href="{$admin_url}acc/reports/statement.php?analytical={$item.id_account}&year={$item.id_year}">Compte de résultat</a>
						| <a href="{$admin_url}acc/reports/balance_sheet.php?analytical={$item.id_account}&year={$item.id_year}">Bilan</a>
					</span>
					</td>
					<td class="money">{$item.sum_expense|raw|html_money}</td>
					<td class="money">{$item.sum_revenue|raw|html_money}</td>
					<td class="money">{$item.debit|raw|html_money:false}</td>
					<td class="money">{$item.credit|raw|html_money:false}</td>
					<td class="money">{$item.sum|raw|html_money:false}</td>
				</tr>
			{/foreach}
			</tbody>
		{/foreach}
	</table>

{else}
	<p class="block alert">
		Il n'y a pas de projet visible en cours.
		{if $current_year && !$analytical_accounts_count}
			{linkbutton label="Créer un nouveau compte de projet" href="!acc/charts/accounts/new.php?id=%d&type=%d"|args:$current_year.id_chart,$analytical_type shape="plus"}
		{else}
			Le solde des projets apparaîtra quand des écritures seront affectées à ces projets.
		{/if}
	</p>
{/if}

{include file="admin/_foot.tpl"}