{include file="admin/_head.tpl" title="Rapprochement : %s — %s"|args:$account.code,$account.label current="acc/accounts"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/accounts/reconcile.php?id={$account.id}">Rapprochement manuel</a></li>
		<li><a href="{$admin_url}acc/accounts/reconcile_assist.php?id={$account.id}">Rapprochement assisté</a></li>
	</ul>
</nav>

<form method="get" action="{$self_url_no_qs}">
	{if $prev || $next}
	<fieldset class="shortFormRight">
		<legend>Rapprochement par mois</legend>
		<p>
			{if $prev}
				{linkbutton shape="left" href=$prev.url label=$prev.date|date_fr:'F Y'}
			{/if}
			{if $next}
				{linkbutton shape="right" href=$next.url label=$next.date|date_fr:'F Y'}
			{/if}
		</p>
	</fieldset>
	{/if}
	<fieldset class="shortFormLeft">
		<legend>Période de rapprochement</legend>
		<p>
			Du
			{input type="date" name="start" default=$start}
			au
			{input type="date" name="end" default=$end}
		</p>
		<p>
			<label>{input type="checkbox" name="only" value=1 default=$only} Seulement les écritures non rapprochées</label>
			<input type="hidden" name="id" value="{$account.id}" />
			<input type="submit" value="Afficher" />
		</p>
	</fieldset>
</form>

<p class="block help">
	Les écritures apparaissent ici dans le sens du relevé de banque, à l'inverse des journaux comptables.
</p>

{form_errors}

<form method="post" action="{$self_url}">
	<table class="list">
		<thead>
			<tr>
				<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
				<td></td>
				<td>Date</td>
				<td class="money">Débit</td>
				<td class="money">Crédit</td>
				<td class="money">Solde cumulé</td>
				<td class="money">Solde rapproché</td>
				<th>Libellé</th>
				<th>Réf. écriture</th>
				<th>Réf. ligne</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$journal item="line"}
			{if isset($line->sum)}
			<tr>
				<td colspan="5"></td>
				<td class="money">{if $line.sum > 0}-{/if}{$line.sum|abs|raw|html_money:false}</td>
				<td class="money">{if $line.reconciled_sum > 0}-{/if}{$line.reconciled_sum|abs|raw|html_money}</td>
				<th>Solde au {$line.date|date_short}</th>
				<td colspan="2"></td>
			</tr>
			{else}
			<tr>
				<td class="check">
					{input type="checkbox" name="reconcile[%d]"|args:$line.id_line value="1" default=$line.reconciled}
				</td>
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
				<td>{$line.date|date_short}</td>
				<td class="money">{$line.credit|raw|html_money}</td>
				<td class="money">{$line.debit|raw|html_money}</td> {* Not a bug! Credit/debit is reversed here to reflect the bank statement *}
				<td class="money">{if $line.running_sum > 0}-{/if}{$line.running_sum|abs|raw|html_money:false}</td>
				<td class="money">{if $line.reconciled_sum > 0}-{/if}{$line.reconciled_sum|abs|raw|html_money:false}</td>
				<th>{$line.label}</th>
				<td>{$line.reference}</td>
				<td>{$line.line_reference}</td>
			</tr>
			{/if}
		{/foreach}
		</tbody>
	</table>
	<p class="submit">
		{csrf_field key="acc_reconcile_%s"|args:$account.id}
		{button type="submit" name="save" label="Enregistrer" class="main" shape="check"}
		{if $next}
			{button type="submit" name="save_next" label="Enregistrer et aller au mois suivant" class="main minor" shape="right"}
		{/if}
	</p>
</form>

{include file="admin/_foot.tpl"}