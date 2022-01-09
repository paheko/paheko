<div class="year-header">

	<nav class="tabs noprint">
		<ul>
		{if isset($analytical) || $current == 'analytical_ledger'}
			<li><strong><a href="{$admin_url}acc/reports/projects.php">Projets</a></strong></li>
		{/if}
		{if $current == 'analytical_ledger'}
				<li class="current"><a href="{$admin_url}acc/reports/ledger.php?{$criterias_query_no_compare}">Grand livre analytique</a></li>
		{else}
			<li{if $current == "graphs"} class="current"{/if}><a href="{$admin_url}acc/reports/graphs.php?{$criterias_query_no_compare}">Graphiques</a></li>
			<li{if $current == "trial_balance"} class="current"{/if}><a href="{$admin_url}acc/reports/trial_balance.php?{$criterias_query_no_compare}">Balance générale</a></li>
			<li{if $current == "journal"} class="current"{/if}><a href="{$admin_url}acc/reports/journal.php?{$criterias_query_no_compare}">Journal général</a></li>
			<li{if $current == "ledger"} class="current"{/if}><a href="{$admin_url}acc/reports/ledger.php?{$criterias_query_no_compare}">Grand livre</a></li>
			<li{if $current == "statement"} class="current"{/if}><a href="{$admin_url}acc/reports/statement.php?{$criterias_query}">Compte de résultat</a></li>
			<li{if $current == "balance_sheet"} class="current"{/if}><a href="{$admin_url}acc/reports/balance_sheet.php?{$criterias_query}">Bilan</a></li>
		{/if}
		</ul>
	</nav>

	<h2>{$config.nom_asso} — {$title}</h2>
	{if isset($analytical)}
		<h3>Projet&nbsp;: {$analytical.label}</h3>
	{/if}
	{if isset($year)}
		<p>Exercice&nbsp;: {$year.label} ({if $year.closed}clôturé{else}en cours{/if}, du
			{$year.start_date|date_short} au {$year.end_date|date_short}, généré le {$close_date|date_short})</p>
	{/if}

	{if !empty($allow_compare) && !empty($other_years)}
	<form method="get" action="" class="noprint">
		<fieldset>
			<legend>Comparer avec un autre exercice</legend>
			<p>
				{input type="select" name="compare_year" options=$other_years default=$criterias.compare_year}
				{button type="submit" label="Comparer" shape="right"}
			</p>
			<input type="hidden" name="year" value="{$year.id}" />
			{if isset($analytical)}
				<input type="hidden" name="analytical" value="{$analytical.id}" />
			{/if}
		</fieldset>
	</form>
	{/if}

	<p class="noprint print-btn">
		<button onclick="window.print(); return false;" class="icn-btn" data-icon="⎙">Imprimer</button>
		{linkbutton shape="download" href="%s&_pdf"|args:$self_url label="Télécharger en PDF"}
	</p>
</div>
