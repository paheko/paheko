{include file="admin/_head.tpl" title="Exercices" current="acc/years"}

{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
<nav class="tabs">
	<ul>
		<li class="current"><a href="{$self_url}">Exercices</a></li>
		<li><a href="{$admin_url}acc/years/new.php">Nouvel exercice</a></li>
	</ul>
</nav>
{/if}

{if $_GET.msg == 'OPEN'}
<p class="error">
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
				<img src="{$admin_url}acc/reports/graph_plot_all.php?type=result" alt="" />
			</figure>
			<figure>
				<img src="{$admin_url}acc/reports/graph_plot_all.php?type=assets" alt="" />
			</figure>
		</section>
	</section>
	{/if}

	<dl class="list">
	{foreach from=$list item="year"}
		<dt>{$year.label}</dt>
		<dd class="desc">
			{if $year.closed}Clôturé{else}En cours{/if}
			| Du {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}
		</dd>
		<dd class="desc">
			<a href="{$admin_url}acc/reports/graphs.php?year={$year.id}">Graphiques</a>
			| <a href="{$admin_url}acc/reports/journal.php?year={$year.id}">Journal général</a>
			| <a href="{$admin_url}acc/reports/ledger.php?year={$year.id}">Grand livre</a>
			| <a href="{$admin_url}acc/reports/trial_balance.php?year={$year.id}">Balance générale</a>
			| <a href="{$admin_url}acc/reports/statement.php?year={$year.id}">Compte de résultat</a>
			| <a href="{$admin_url}acc/reports/balance_sheet.php?year={$year.id}">Bilan</a>
		</dd>
		{if $session->canAccess('compta', Membres::DROIT_ADMIN) && !$year.closed}
		<dd class="actions">
			{linkbutton label="Balance d'ouverture" shape="reset" href="acc/years/balance.php?id=%d"|args:$year.id}
			{linkbutton label="Modifier" shape="edit" href="acc/years/edit.php?id=%d"|args:$year.id}
			{linkbutton label="Clôturer" shape="lock" href="acc/years/close.php?id=%d"|args:$year.id}
			{linkbutton label="Supprimer" shape="delete" href="acc/years/delete.php?id=%d"|args:$year.id}
		</dd>
		{/if}
	{/foreach}
	</dl>
{else}
	<p class="alert">
		Il n'y a pas d'exercice en cours.
	</p>
{/if}

{include file="admin/_foot.tpl"}