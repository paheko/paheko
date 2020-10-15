{include file="admin/_head.tpl" title="Comptabilité" current="acc"}

{foreach from=$years item="year"}
<section class="year-infos">
	<h2 class="ruler">{$year.label} —
		Du {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}</h2>

	<nav class="tabs">
		<ul>
			<li><a href="{$admin_url}acc/reports/journal.php?year={$year.id}">Journal général</a></li>
			<li><a href="{$admin_url}acc/reports/ledger.php?year={$year.id}">Grand livre</a></li>
			<li><a href="{$admin_url}acc/reports/trial_balance.php?year={$year.id}">Balance générale</a></li>
			<li><a href="{$admin_url}acc/reports/statement.php?year={$year.id}">Compte de résultat</a></li>
			<li><a href="{$admin_url}acc/reports/balance_sheet.php?year={$year.id}">Bilan</a></li>
			<li><a href="{$admin_url}acc/transactions/search.php?year={$year.id}"><strong>Recherche</strong></a></li>
			{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
				<li><a href="{$admin_url}acc/years/import.php?id={$year.id}">Import / export</a></li>
			{/if}
		</ul>
	</nav>

	<section class="graphs">
		{foreach from=$graphs key="url" item="label"}
		<figure>
			<img src="{$url|args:$year.id}" alt="" />
			<figcaption>{$label}</figcaption>
		</figure>
		{/foreach}
	</section>
</section>


{foreachelse}
	<p class="alert">
		Il n'y a aucun exercice ouvert en cours.<br />
		{linkbutton label="Ouvrir un nouvel exercice" shape="plus" href="acc/years/new.php"}
	</p>
{/foreach}

{include file="admin/_foot.tpl"}