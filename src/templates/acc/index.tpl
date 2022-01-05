{include file="admin/_head.tpl" title="Comptabilité" current="acc"}

{if !empty($all_years)}
<form method="get" action="{$admin_url}acc/search.php" class="shortForm">
	<fieldset>
		<legend>Recherche rapide</legend>
		<p>
			<input type="search" name="qt" value="" />
			{input type="select" name="year" options=$all_years default=$first_year}
			{button type="submit" shape="search" label="Chercher"}
		</p>
		<p class="help">
			Indiquer un numéro de compte, un montant précédé par le signe égal (<code>=62,41</code>) ou une date (<code>JJ/MM/AAAA</code>), sinon la recherche sera effectuée dans le libellé ou la pièce comptable.
		</p>
	</fieldset>
</form>
{/if}

{foreach from=$years item="year"}
<section class="year-infos">
	<h2 class="ruler">{$year.label} —
		Du {$year.start_date|date_short} au {$year.end_date|date_short}</h2>

	<nav class="tabs">
		<aside>
			{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
				{linkbutton shape="upload" href="!acc/years/import.php?id=%d"|args:$year.id label="Import & export"}
			{/if}
			{linkbutton shape="search" href="!acc/search.php?year=%d"|args:$year.id label="Recherche"}
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

	{if $year.nb_transactions > 3}
		<section class="graphs">
			{foreach from=$graphs key="url" item="label"}
			<figure>
				<img src="{$url|args:'year='|cat:$year.id}" alt="" />
				<figcaption>{$label}</figcaption>
			</figure>
			{/foreach}
		</section>
	{else}
		<p class="help block">Il n'y a pas encore suffisamment d'écritures dans cet exercice pour pouvoir afficher les statistiques.</p>
		<p>{linkbutton label="Saisir une nouvelle écriture" shape="plus" href="transactions/new.php"}</p>
	{/if}
</section>


{foreachelse}
	<p class="block alert">
		Il n'y a aucun exercice ouvert en cours.<br />
		{linkbutton label="Ouvrir un nouvel exercice" shape="plus" href="!acc/years/new.php"}
	</p>
{/foreach}

{include file="admin/_foot.tpl"}