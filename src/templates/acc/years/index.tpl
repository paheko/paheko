{include file="_head.tpl" title="Exercices" current="acc/years"}

<nav class="tabs">
	<aside>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			{linkbutton shape="plus" href="!acc/years/new.php" label="Nouvel exercice"}
		{/if}
		{linkbutton shape="search" href="!acc/search.php" label="Recherche"}
	</aside>
	<ul>
		<li class="current"><a href="{$self_url}">Exercices</a></li>
		<li><a href="{$admin_url}acc/projects/">Projets <em>(compta analytique)</em></a></li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		<li><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
		{/if}
	</ul>
</nav>

{if $_GET.msg == 'IMPORT'}
<p class="block confirm">
	L'import s'est bien déroulé.
</p>
{/if}

{if $_GET.msg == 'WELCOME'}
<div class="block confirm">
	<h3>Votre premier exercice a été créé&nbsp;!</h3>
	<p>Vous pouvez désormais utiliser la comptabilité.</p>
	<p>{linkbutton shape="plus" href="!acc/transactions/new.php" label="Saisir une écriture"}</p>
</div>
{/if}

{if $_GET.msg == 'OPEN'}
<p class="block error">
	Il n'existe aucun exercice ouvert.
	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		Merci d'en <a href="{$admin_url}acc/years/new.php">créer un nouveau</a> pour pouvoir saisir des écritures.
	{/if}
</p>
{/if}

{if $_GET.msg == 'UPDATE_FEES'}
<p class="block error">
	Des tarifs d'activité étaient associés à l'ancien exercice clôturé.
	Ces tarifs ont été déconnectés de la comptabilité à cause du changement de plan comptable, il vous faudra les reconnecter manuellement au nouvel exercice.
</p>
{/if}

{if !empty($list)}
	{if count($list) > 1}
	<section class="year-infos">
		<section class="graphs">
			<figure>
				<img src="{$admin_url}acc/reports/graph_plot_all.php?type=assets" alt="" />
				<figcaption>Soldes des banques et caisses par exercice</figcaption>
			</figure>
			<figure>
				<img src="{$admin_url}acc/reports/graph_plot_all.php?type=result" alt="" />
				<figcaption>Recettes et dépenses par exercice</figcaption>
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
				<td>{if $year.closed}<em>Clôturé</em>{else}<strong class="confirm">En cours</strong>{/if}</td>
				<td>
				{linkbutton label="Export" shape="export" href="export.php?year=%d"|args:$year.id}
				{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN) && !$year.closed}
					{linkbutton label="Import" shape="upload" href="import.php?year=%d"|args:$year.id}
					{linkbutton label="Balance d'ouverture" shape="reset" href="balance.php?id=%d"|args:$year.id}
					{linkbutton label="Modifier" shape="edit" href="edit.php?id=%d"|args:$year.id}
					{linkbutton label="Clôturer" shape="lock" href="close.php?id=%d"|args:$year.id}
					{linkbutton label="Supprimer" shape="delete" href="delete.php?id=%d"|args:$year.id}
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

{include file="_foot.tpl"}