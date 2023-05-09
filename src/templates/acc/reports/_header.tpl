<div class="year-header">
	<nav class="tabs noprint">
		{if !empty($year)}
		<aside>
			{if $current !== 'graphs'}
				{exportmenu class="menu-btn-right" xlsx=true suffix="_export="}
			{/if}
			{if !$criterias.before && !$criterias.compare_year && !empty($allow_compare) && !empty($other_years)}
				{linkbutton shape="list-ol" href="#" id="compareFormButton" label="Comparer" onclick="var a = $('#compareForm'); a.disabled = false; g.toggle(a, true); this.remove(); var a = $('#filterFormButton'); a ? a.remove() : null; return false;"}
			{/if}
			{if !$criterias.compare_year  && !empty($allow_filter) && !$criterias.before && !$criterias.after}
				{linkbutton shape="search" href="#" id="filterFormButton" label="Filtrer" onclick="var a = $('#filterForm'); a.disabled = false; g.toggle(a, true); this.remove(); var a = $('#compareFormButton'); a ? a.remove() : null; return false;"}
			{/if}
		</aside>
		{/if}

		<ul>
		{if isset($project) || $current == 'analytical_ledger'}
			<li><strong><a href="{$admin_url}acc/projects/">Projets</a></strong></li>
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

	{if !empty($year)}
	<div class="forms">
		{if !empty($allow_compare) && !empty($other_years)}
		<form method="get" action="" class="{if !$criterias.compare_year}hidden {/if}noprint" id="compareForm">
			<input type="hidden" name="year" value="{$year.id}" />
			{if isset($project)}
				<input type="hidden" name="project" value="{$project.id}" />
			{/if}
			<fieldset>
				<legend>Comparer avec un autre exercice</legend>
				<p>
					{input type="select" name="compare_year" options=$other_years default=$criterias.compare_year}
					{button type="submit" label="OK" shape="right"}
				</p>
			</fieldset>
		</form>
		{/if}

		{if !empty($allow_filter)}
		<form method="get" action="" class="{if !$criterias.before}hidden {/if}noprint" id="filterForm">
			<input type="hidden" name="year" value="{$year.id}" />
			{if isset($project)}
				<input type="hidden" name="project" value="{$project.id}" />
			{/if}
			<fieldset>
				<legend>Filtrer par date</legend>
				<p>
					<label for="f_after">Du</label>
					{input type="date" name="after" default=$after_default}
					<label for="f_before">au</label>
					{input type="date" name="before" default=$before_default}
					{button type="submit" label="OK" shape="right"}
					<input type="submit" value="Annuler" onclick="this.form.querySelectorAll('input:not([type=hidden]), select').forEach((a) => a.disabled = true); this.form.submit();" />
				</p>
			</fieldset>
		</form>
		{/if}
	</div>
	{/if}

	{if $config.files.logo}
	<figure class="logo print-only"><img src="{$config->fileURL('logo', '150px')}" alt="" /></figure>
	{/if}

	<h2>{$config.org_name} — {$title}</h2>
	{if isset($project)}
		<h3>Projet&nbsp;: {if $project.code}{$project.code} — {/if}{$project.label}{if $project.archived} <em>(archivé)</em>{/if}</h3>
	{/if}
	{if isset($year)}
		<p>Exercice&nbsp;: {$year.label} ({if $year.closed}clôturé{else}<strong>en cours</strong>{/if})
			— du {$year.start_date|date_short}
			— au {$year.end_date|date_short}<br />
			<small><em>Document généré le {$now|date_short}</em></small>
		</p>
	{/if}

	<p class="noprint print-btn">
		<button onclick="window.print(); return false;" class="icn-btn" data-icon="⎙">Imprimer</button>
		{if $current != 'graphs' && PDF_COMMAND}
		{linkbutton shape="download" href="%s&_pdf"|args:$self_url label="Télécharger en PDF"}
		{/if}
	</p>
</div>

	{if !empty($allow_filter) && isset($year) && $criterias.before && $criterias.after}
		<p class="alert block">
			Attention, seules les écritures du {$criterias.after|date_short} au {$criterias.before|date_short} sont prises en compte.
		</p>
	{/if}
