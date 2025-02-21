{use Paheko\Entities\Accounting\Year}
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
{else if $_GET.msg == 'REOPEN'}
	<p class="block confirm">
		L'exercice sélectionné a été réouvert.
	</p>
{elseif $_GET.msg == 'WELCOME'}
	<div class="block confirm">
		<h3>Votre premier exercice a été créé&nbsp;!</h3>
		<p>Vous pouvez désormais utiliser la comptabilité.</p>
		<p>{linkbutton shape="plus" href="!acc/transactions/new.php" label="Saisir une écriture"}</p>
	</div>
{elseif $_GET.msg == 'OPEN'}
	<p class="block error">
		Il n'existe aucun exercice ouvert.
		Merci d'en créer un pour pouvoir saisir des écritures.
	</p>
{elseif $_GET.msg == 'UPDATE_FEES'}
	<p class="block error">
		Des tarifs d'activité étaient associés à l'ancien exercice clôturé.
		Ces tarifs ont été déconnectés de la comptabilité à cause du changement de plan comptable, il vous faudra les reconnecter manuellement au nouvel exercice.
	</p>
{/if}

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

<section class="years">
{foreach from=$list item="year"}
	<article>
		<header>
			<div>
				<h2>
					{$year.label}
					{tag preset=$year.status_tag_preset}
				</h2>
				<h3>{$year.start_date|date_short} au {$year.end_date|date_short}</h3>
			</div>
			<div class="details">
				<p class="chart">{link href="../charts/accounts/?id=%d"|args:$year.id_chart label=$year.chart_name}</p>
				<p class="count">{$year.nb_transactions} écritures</p>
			</div>
		</header>
		<p class="actions">
			{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
				{if $year.status === Year::CLOSED}
					{linkbutton label="Ré-ouvrir" shape="reset" href="reopen.php?id=%d"|args:$year.id target="_dialog"}
				{elseif $year.status === Year::LOCKED}
					{linkbutton label="Déverrouiller" shape="unlock" href="edit.php?id=%d"|args:$year.id target="_dialog"}
				{else}
					{linkmenu label="Modifier…" shape="edit"}
						{linkbutton label="Modifier" shape="edit" href="edit.php?id=%d"|args:$year.id target="_dialog"}
						{linkbutton label="Balance d'ouverture" shape="money" href="balance.php?id=%d"|args:$year.id}
						{linkbutton label="Importer" shape="import" href="import.php?year=%d"|args:$year.id}
						{*linkbutton label="Déplacer des écritures" shape="reload" href="lock.php?id=%d"|args:$year.id target="_dialog"*}
						{*linkbutton label="Verrouiller temporairement" shape="lock" href="lock.php?id=%d"|args:$year.id target="_dialog"*}
						{linkbutton label="Clôturer définitivement" shape="delete" href="close.php?id=%d"|args:$year.id target="_dialog"}
						{linkbutton label="Supprimer" shape="trash" href="delete.php?id=%d"|args:$year.id target="_dialog"}
					{/linkmenu}
				{/if}
			{/if}
			{*linkbutton label="Télécharger" shape="download" href="download.php?year=%d"|args:$year.id target="_dialog"*}
			{linkbutton label="Exporter" shape="export" href="export.php?year=%d"|args:$year.id}
		</p>
		<p class="reports">
			{linkbutton href="!acc/reports/graphs.php?year=%d"|args:$year.id label="Graphiques"}
			{linkbutton href="!acc/reports/trial_balance.php?year=%d"|args:$year.id label="Balance générale"}
			{linkbutton href="!acc/reports/journal.php?year=%d"|args:$year.id label="Journal général"}
			{linkbutton href="!acc/reports/ledger.php?year=%d"|args:$year.id label="Grand livre"}
			{linkbutton href="!acc/reports/statement.php?year=%d"|args:$year.id label="Compte de résultat"}
			{linkbutton href="!acc/reports/balance_sheet.php?year=%d"|args:$year.id label="Bilan"}
		</p>
	</article>
{/foreach}
</section>

{include file="_foot.tpl"}