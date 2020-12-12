{include file="admin/_head.tpl" title="Exercices" current="acc/years"}

<nav class="tabs">
	<aside>
		{linkbutton shape="search" href="!acc/search.php" label="Recherche"}
	</aside>
	<ul>
		<li class="current"><a href="{$self_url}">Exercices</a></li>
		{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
		<li><a href="{$admin_url}acc/years/new.php">Nouvel exercice</a></li>
		{/if}
		<li><a href="{$admin_url}acc/reports/projects.php">Projets <em>(compta analytique)</em></a></li>
	</ul>
</nav>

{if $_GET.msg == 'OPEN'}
<p class="block error">
	Il n'existe aucun exercice ouvert.
	{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
		Merci d'en <a href="{$admin_url}acc/years/new.php">créer un nouveau</a> pour pouvoir saisir des écritures.
	{/if}
</p>
{/if}

{if !empty($list)}
	{if count($list) > 1}
	<section class="year-infos">
		<section class="graphs">
			<figure>
				<img src="{$admin_url}acc/reports/graph_plot_all.php?type=assets" alt="" />
			</figure>
			<figure>
				<img src="{$admin_url}acc/reports/graph_plot_all.php?type=result" alt="" />
			</figure>
		</section>
	</section>
	{/if}

	<table class="list">
	{foreach from=$list item="year"}
		<tbody>
			<tr>
				<th><h3>{$year.label}</h3></th>
				<td>{$year.nb_transactions} écritures | <a href="../charts/accounts/?id={$year.id_chart}">{$year.chart_name}</a></td>
			</tr>
			<tr>
				<td>{$year.start_date|date_short} au {$year.end_date|date_short}</td>
				<td>
					<a href="{$admin_url}acc/reports/graphs.php?year={$year.id}">Graphiques</a>
					| <a href="{$admin_url}acc/reports/trial_balance.php?year={$year.id}">Balance générale</a>
					| <a href="{$admin_url}acc/reports/journal.php?year={$year.id}">Journal général</a>
					| <a href="{$admin_url}acc/reports/ledger.php?year={$year.id}">Grand livre</a>
					| <a href="{$admin_url}acc/reports/statement.php?year={$year.id}">Compte de résultat</a>
					| <a href="{$admin_url}acc/reports/balance_sheet.php?year={$year.id}">Bilan</a>
				</td>
			</tr>
			<tr>
				<td><em>{if $year.closed}Clôturé{else}En cours{/if}</em></td>
				<td>
				{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
					{linkbutton label="Export CSV" shape="export" href="import.php?id=%d&export=csv"|args:$year.id}
					{linkbutton label="Export tableur" shape="export" href="import.php?id=%d&export=ods"|args:$year.id}
					{if !$year.closed}
						{linkbutton label="Import" shape="upload" href="import.php?id=%d"|args:$year.id}
						{linkbutton label="Balance d'ouverture" shape="reset" href="balance.php?id=%d"|args:$year.id}
						{linkbutton label="Modifier" shape="edit" href="edit.php?id=%d"|args:$year.id}
						{linkbutton label="Clôturer" shape="lock" href="close.php?id=%d"|args:$year.id}
						{linkbutton label="Supprimer" shape="delete" href="delete.php?id=%d"|args:$year.id}
					{/if}
				{/if}
				</td>
			</tr>
		</tbody>
	{/foreach}
	</table>
{else}
	<p class="block alert">
		Il n'y a pas d'exercice en cours.
	</p>
{/if}

{include file="admin/_foot.tpl"}