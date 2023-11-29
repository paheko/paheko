{include file="_head.tpl" title="Comptabilité" current="acc"}

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
			Indiquer un numéro de compte, un numéro d'écriture précédé par le signe hash (<code>#1234</code>), un montant précédé par le signe égal (<code>=62,41</code>) ou une date (<code>JJ/MM/AAAA</code>), sinon la recherche sera effectuée sur le libellé ou la pièce comptable.
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
				{linkbutton shape="upload" href="!acc/years/import.php?year=%d"|args:$year.id label="Import & export"}
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
		<section class="graphs small">
			{foreach from=$graphs key="url" item="label"}
			<figure>
				<img src="{$url|args:'year='|cat:$year.id}" alt="" />
				<figcaption>{$label}</figcaption>
			</figure>
			{/foreach}
		</section>
	{else}
		<p class="help block">Il n'y a pas encore suffisamment d'écritures dans cet exercice pour pouvoir afficher les statistiques.</p>
		<p>{linkbutton label="Saisir une nouvelle écriture" shape="plus" href="transactions/new.php?set_year=%d"|args:$year.id}</p>
	{/if}

	{if $year.nb_transactions}
	<?php $list = $last_transactions[$year->id]; ?>
	<h3 class="ruler">Dernières écritures</h3>
	{include file="common/dynamic_list_head.tpl" check=false disable_user_ordering=true}
			{foreach from=$list->iterate() item="line"}
			<tr>
				<td>{$line.type_label}</td>
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
				<td>{$line.date|date_short}</td>
				<td class="money">{$line.change|abs|raw|money}</td>
				<td>{$line.reference}</td>
				<th>{$line.label}</th>
				<td>{$line.line_reference}</td>
				<td class="num">{foreach from=$line.project_code item="code" key="id"}<a href="{$admin_url}acc/reports/statement.php?project={$id}">{$code}</a> {/foreach}</td>
				<td>{if $line.files}{$line.files}{/if}</td>
				<td class="actions">
					{linkbutton href="!acc/transactions/details.php?id=%d"|args:$line.id label="Détails" shape="search"}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
	{/if}
</section>


{foreachelse}
	<p class="block alert">
		Il n'y a aucun exercice ouvert en cours.<br />
		{linkbutton label="Ouvrir un nouvel exercice" shape="plus" href="!acc/years/new.php"}
	</p>
{/foreach}

{include file="_foot.tpl"}