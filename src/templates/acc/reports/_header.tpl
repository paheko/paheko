<div class="year-header">

	<nav class="tabs noprint">
		<ul>
			{if isset($analytical)}
			<li><strong><a href="{$admin_url}acc/reports/projects.php">Projets</a></strong></li>
			{/if}
			<li{if $current == "graphs"} class="current"{/if}><a href="{$admin_url}acc/reports/graphs.php?{$criterias_query}">Graphiques</a></li>
			<li{if $current == "trial_balance"} class="current"{/if}><a href="{$admin_url}acc/reports/trial_balance.php?{$criterias_query}">Balance générale</a></li>
			<li{if $current == "journal"} class="current"{/if}><a href="{$admin_url}acc/reports/journal.php?{$criterias_query}">Journal général</a></li>
			<li{if $current == "ledger"} class="current"{/if}><a href="{$admin_url}acc/reports/ledger.php?{$criterias_query}">Grand livre</a></li>
			<li{if $current == "statement"} class="current"{/if}><a href="{$admin_url}acc/reports/statement.php?{$criterias_query}">Compte de résultat</a></li>
			<li{if $current == "balance_sheet"} class="current"{/if}><a href="{$admin_url}acc/reports/balance_sheet.php?{$criterias_query}">Bilan</a></li>
		</ul>
	</nav>

	<h2>{$config.nom_asso} — {$title}</h2>
	{if isset($analytical)}
		<h3>Projet&nbsp;: {$analytical.label}</h3>
	{/if}
	{if isset($year)}
		<p>Exercice comptable {if $year.closed}clôturé{else}en cours{/if} du
			{$year.start_date|date_short} au {$year.end_date|date_short}, généré le {$close_date|date_short}</p>
	{/if}

	<p class="noprint print-btn">
		<button onclick="window.print(); return false;" class="icn-btn" data-icon="⎙">Imprimer</button>
	</p>
</div>
