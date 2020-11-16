{include file="admin/_head.tpl" title="Comptabilité" current="acc"}

{foreach from=$years item="year"}
<section class="year-infos">
	<h2 class="ruler">{$year.label} —
		Du {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}</h2>

	<nav class="tabs">
		<aside>
			{linkbutton shape="search" href="!acc/search.php?year=%d"|args:$year.id label="Recherche"}
			{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
				{linkbutton shape="upload" href="!acc/years/import.php?year=%d"|args:$year.id label="Import & export"}
			{/if}
		</aside>
		<ul>
			<li><a href="{$admin_url}acc/reports/graphs.php?year={$year.id}">Graphiques</a></li>
			<li><a href="{$admin_url}acc/reports/trial_balance.php?year={$year.id}">Balance générale</a></li>
			<li><a href="{$admin_url}acc/reports/journal.php?year={$year.id}">Journal général</a></li>
			<li><a href="{$admin_url}acc/reports/ledger.php?year={$year.id}">Grand livre</a></li>
			<li><a href="{$admin_url}acc/reports/statement.php?year={$year.id}">Compte de résultat</a></li>
			<li><a href="{$admin_url}acc/reports/balance_sheet.php?year={$year.id}">Bilan</a></li>
		</ul>
	</nav>

	{if $year.has_transactions}
	<section class="graphs">
		{foreach from=$graphs key="url" item="label"}
		<figure>
			<img src="{$url|args:'year='|cat:$year.id}" alt="" />
			<figcaption>{$label}</figcaption>
		</figure>
		{/foreach}
	</section>
	{/if}
</section>


{foreachelse}
	<p class="block alert">
		Il n'y a aucun exercice ouvert en cours.<br />
		{linkbutton label="Ouvrir un nouvel exercice" shape="plus" href="!acc/years/new.php"}
	</p>
{/foreach}

{include file="admin/_foot.tpl"}