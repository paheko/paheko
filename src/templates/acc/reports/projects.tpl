{include file="admin/_head.tpl" title="Projets" current="acc/years"}

<nav class="tabs">
	{if CURRENT_YEAR_ID}
	<aside>
		{linkbutton label="Créer un nouveau compte de projet" href="acc/charts/accounts/new.php?id=%d&type=%d"|args:$current_year.id_chart,$analytical_type shape="plus"}
	</aside>
	{/if}

	<ul>
		<li><a href="{$admin_url}acc/years/">Exercices</a></li>
		{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
		<li><a href="{$admin_url}acc/years/new.php">Nouvel exercice</a></li>
		{/if}
		<li class="current"><a href="{$admin_url}acc/reports/projects.php">Projets <em>(compta analytique)</em></a></li>
	</ul>
</nav>

{if !empty($list)}


	<table class="list">
		<thead>
			<tr>
				<td>Année</td>
				<td></td>
				<td class="money">Débits</td>
				<td class="money">Crédits</td>
				<td class="money">Solde</td>
			</tr>
		</thead>
		{foreach from=$list item="account"}
			<tbody>
				<tr>
					<th colspan="5">
						<h2 class="ruler">{$account.label}</h2>
					</th>
				</tr>
			{foreach from=$account.years item="year"}
				<tr>
					<th>{$year.year_label}</th>
					<td>
						<a href="{$admin_url}acc/reports/graphs.php?analytical={$account.id}&year={$year.id_year}">Graphiques</a>
						| <a href="{$admin_url}acc/reports/trial_balance.php?analytical={$account.id}&year={$year.id_year}">Balance générale</a>
						| <a href="{$admin_url}acc/reports/journal.php?analytical={$account.id}&year={$year.id_year}">Journal général</a>
						| <a href="{$admin_url}acc/reports/ledger.php?analytical={$account.id}&year={$year.id_year}">Grand livre</a>
						| <a href="{$admin_url}acc/reports/statement.php?analytical={$account.id}&year={$year.id_year}">Compte de résultat</a>
						| <a href="{$admin_url}acc/reports/balance_sheet.php?analytical={$account.id}&year={$year.id_year}">Bilan</a>
					</td>
					<td class="money">{$year.debit|raw|html_money}</td>
					<td class="money">{$year.credit|raw|html_money}</td>
					<td class="money">{$year.sum|raw|html_money:false}</td>
				</tr>
			{/foreach}
			</tbody>
		{/foreach}
	</table>

{else}
	<p class="block alert">
		Il n'y a pas de projet visible en cours.
		{if $current_year && !$analytical_accounts_count}
			{linkbutton label="Créer un nouveau compte de projet" href="acc/charts/accounts/new.php?id=%d&type=%d"|args:$current_year.id_chart,$analytical_type shape="plus"}
		{else}
			Le solde des projets apparaîtra quand des écritures seront affectées à ces projets.
		{/if}
	</p>
{/if}

{include file="admin/_foot.tpl"}