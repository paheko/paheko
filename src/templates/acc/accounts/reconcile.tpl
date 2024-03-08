{include file="_head.tpl" title="Rapprochement : %s — %s"|args:$account.code,$account.label current="acc/accounts"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<aside>
		{linkbutton shape="search" label="Filtres avancés" onclick="g.toggle('.advanced-filters', true)" href="#nojs"}
	</aside>
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
				{linkbutton shape="left" href=$prev.url label=$prev.date|date:'F Y'}
			{/if}
			{if $next}
				{linkbutton shape="right" href=$next.url label=$next.date|date:'F Y'}
			{/if}
		</p>
	</fieldset>
	{/if}

	<fieldset class="shortFormLeft">
		<legend>Rapprochement</legend>
		<p>
			Du
			{input type="date" name="start" default=$start required=true}
			au
			{input type="date" name="end" default=$end required=true}
			<input type="hidden" name="id" value="{$account.id}" />
			{button type="submit" label="Afficher"}
		</p>
		<dl class="advanced-filters {if !$only && !$final_sum}hidden{/if}">
			{input type="checkbox" name="only" value=1 default=$only label="Seulement les écritures non rapprochées"}
			{*input type="money" label="Solde final du relevé de compte" help="Indiquer ici le solde situé à la fin du relevé de compte" name="final_sum"*}
		</dl>
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
				<td class="money">{if $line.sum > 0}-{/if}{$line.sum|abs|raw|money:false}</td>
				<td class="money" data-credit="0">{if $line.reconciled_sum > 0}-{/if}{$line.reconciled_sum|abs|raw|money}</td>
				<th>Solde au {$line.date|date_short}</th>
				<td colspan="2"></td>
			</tr>
			{else}
			<tr{if $line.reconciled} class="disabled"{/if}>
				<td class="check">
					{input type="checkbox" name="reconcile[%d]"|args:$line.id_line value="1" default=$line.reconciled onchange="reconcileLine(this);"}
				</td>
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
				<td>{$line.date|date_short}</td>
				<td class="money">{$line.credit|raw|money}</td>
				<td class="money">{$line.debit|raw|money}</td> {* Not a bug! Credit/debit is reversed here to reflect the bank statement *}
				<td class="money">{if $line.running_sum > 0}-{/if}{$line.running_sum|abs|raw|money:false}</td>
				<td class="money" data-credit="{$line.credit}" data-debit="{$line.debit}">{if $line.reconciled_sum > 0}-{/if}{$line.reconciled_sum|abs|raw|money:false}</td>
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

<script type="text/javascript">
{literal}
function reconcileLine(elm)
{
	var row = elm.parentNode.parentNode;
	var col = row.querySelector('td[data-credit]');
	// Not a bug! Credit/debit is reversed here to reflect the bank statement
	var change = parseInt(col.dataset.debit, 10) || -(parseInt(col.dataset.credit, 10));
	var sum = g.getMoneyAsInt(col.innerText);

	if (!elm.checked) {
		change *= -1;
	}

	col.innerHTML = g.formatMoney(sum + change);

	while (row = row.nextElementSibling) {
		col = row.querySelector('td[data-credit]');
		sum = g.getMoneyAsInt(col.innerText);
		col.innerHTML = g.formatMoney(sum + change);
	}
}
{/literal}
</script>

{include file="_foot.tpl"}