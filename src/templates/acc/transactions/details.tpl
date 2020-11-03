{include file="admin/_head.tpl" title="Écriture n°%d"|args:$transaction.id current="acc"}

{if $session->canAccess('compta', Membres::DROIT_ADMIN) && !$transaction->validated && !$tr_year->closed}
<nav class="tabs">
	<ul>
		<li><a href="edit.php?id={$transaction.id}">Modifier cette écriture</a></li>
		<li><a href="delete.php?id={$transaction.id}">Supprimer cette écriture</a></li>
	</ul>
</nav>
{/if}

{if $session->canAccess('compta', Membres::DROIT_ECRITURE) && $transaction.status & $transaction::STATUS_WAITING}
<form method="post" action="{$admin_url}acc/transactions/new.php">
<div class="block alert">
	{if $transaction.type == $transaction::TYPE_DEBT}
		<h3>Dette en attente</h3>
		<input type="hidden" name="payoff_for" value="{$transaction.id}" />
		{button shape="check" label="Enregistrer le règlement de cette dette" type="submit"}
	{else}
		<h3>Créance en attente</h3>
		<input type="hidden" name="payoff_for" value="{$transaction.id}" />
		{button shape="export" label="Enregistrer le règlement de cette créance" type="submit"}
	{/if}
</div>
</form>
{/if}

<dl class="describe">
	{if $transaction.id_related}
	<dt>Écriture liée à</dt>
	<dd><a href="{$admin_url}acc/transactions/details.php?id={$transaction.id_related}">#{$transaction.id_related}</a>
		{if $transaction.type == $transaction::TYPE_PAYOFF}(en règlement de){/if}
	</dd>
	{/if}
	<dt>Libellé</dt>
	<dd><h2>{$transaction.label}</h2></dd>
	<dt>Date</dt>
	<dd>{$transaction.date|date_fr:'l j F Y (d/m/Y)'}</dd>
	<dt>Numéro pièce comptable</dt>
	<dd>{if trim($transaction.reference)}{$transaction.reference}{else}-{/if}</dd>

	<dt>Exercice</dt>
	<dd>
		<a href="{$admin_url}acc/reports/ledger.php?year={$transaction.id_year}">{$tr_year.label}</a>
		| Du {$tr_year.start_date|date_fr:'d/m/Y'} au {$tr_year.end_date|date_fr:'d/m/Y'}
		| <strong>{if $tr_year.closed}Clôturé{else}En cours{/if}</strong>
	</dd>

	{if $transaction.id_projet}
		<dt>Projet</dt>
		<dd>
			<a href="{$admin_url}compta/projets/">{$projet.libelle}</a>
		</dd>
	{/if}

	<dt>Opération créée par</dt>
	<dd>
		{if $transaction.id_creator}
			{if $session->canAccess('compta', Membres::DROIT_ACCES)}
				<a href="{$admin_url}membres/fiche.php?id={$transaction.id_creator}">{$creator_name}</a>
			{else}
				{$creator_name}
			{/if}
		{else}
			<em>membre supprimé</em>
		{/if}
	</dd>

	<dt>Opération liée à</dt>
	{if empty($related_users)}
		<dd><em>Aucun membre n'est lié à cette opération.</em></dd>
	{else}
		{foreach from=$related_users item="u"}
			<dd><a href="{$admin_url}membres/fiche.php?id={$u.id}">{$u.identity}</a></dd>
		{/foreach}
	{/if}

	<dt>Remarques</dt>
	<dd>{if trim($transaction.notes)}{$transaction.notes|escape|nl2br}{else}-{/if}</dd>

	<dt>Fichiers joints</dt>
	{foreach from=$files item="file"}
	<dd>
		<aside class="file">
			<a href="{$file.url}">{$file.nom}</a>
			<small>({$file.type}, {$file.taille|format_bytes})</small>
		</aside>
	</dd>
	{foreachelse}
	<dd>-</dd>
	{/foreach}
</dl>

<table class="list">
	<thead>
		<tr>
			<td class="num">N° compte</td>
			<th>Compte</th>
			<td class="money">Débit</td>
			<td class="money">Crédit</td>
			<td>Libellé</td>
			<td>Référence</td>
		</tr>
	</thead>
	<tbody>
		{foreach from=$transaction->getLinesWithAccounts() item="line"}
		<tr>
			<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$line.id_account}&amp;year={$transaction.id_year}">{$line.account_code}</a></td>
			<td>{$line.account_name}</td>
			<td class="money">{if $line.debit}{$line.debit|escape|html_money}{/if}</td>
			<td class="money">{if $line.credit}{$line.credit|escape|html_money}{/if}</td>
			<td>{$line.label}</td>
			<td>{$line.reference}</td>
		</tr>
		{/foreach}
	</tbody>
</table>

{include file="admin/_foot.tpl"}