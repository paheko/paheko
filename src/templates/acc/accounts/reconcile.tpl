{include file="_head.tpl" title="Rapprochement : %s — %s"|args:$account.code:$account.label current="acc/accounts"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<aside>
		{exportmenu table=true}
		{if PDF_COMMAND}
			{linkbutton shape="download" href="%s&_pdf"|args:$self_url label="Télécharger en PDF"}
		{/if}
	{if !$has_advanced_options}
		{linkbutton shape="search" label="Configuration avancée" onclick="toggleFilters(this);" href="#nojs"}
	{/if}
	</aside>

	<ul>
		<li class="current"><a href="{$admin_url}acc/accounts/reconcile.php?id={$account.id}">Rapprochement manuel</a></li>
		<li><a href="{$admin_url}acc/accounts/reconcile_assist.php?id={$account.id}">Rapprochement assisté</a></li>
	</ul>
</nav>

<h2 class="ruler print-only">{"Rapprochement : %s — %s"|args:$account.code:$account.label}</h2>

<form method="get" action="{$self_url_no_qs}" class="noprint">
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

	<fieldset class="shortFormLeft simple-filters {if $has_advanced_options}hidden{/if}">
		<legend>Période de rapprochement</legend>
		<p>
			Du
			{input type="date" name="start" default=$start required=true}
			au
			{input type="date" name="end" default=$end required=true}
			<input type="hidden" name="id" value="{$account.id}" />
			{button type="submit" label="Afficher"}
		</p>
	</fieldset>
	<fieldset class="shortFormLeft advanced-filters {if !$has_advanced_options}hidden{/if}">
		<legend>Configuration du rapprochement</legend>
		<dl>
			{input type="date" name="start" default=$start required=true label="Date de début"}
			{input type="date" name="end" default=$end required=true label="Date de fin"}
			{input type="select" name="filter" default=$filter label="Filtre" options=$filter_options required=true}
			{input type="select" name="desc" default=$desc label="Ordre" options=$desc_options required=true}
			{input type="money" label="Solde initial du relevé de compte" help="Indiquer ici le solde situé au début du relevé de compte." name="sum_start" default=$sum_start}
			{input type="money" label="Solde final du relevé de compte" help="Indiquer ici le solde situé à la fin du relevé de compte." name="sum_end" default=$sum_end}
		</dl>
		<p class="submit">
			<input type="hidden" name="id" value="{$account.id}" />
			{button type="submit" label="Afficher" class="main minor" shape="right"}
		</p>
	</fieldset>
</form>

<p class="block help">
	Les écritures apparaissent ici dans le sens du relevé de banque, à l'inverse des journaux comptables.
</p>

{if $has_unreconciled}
<p class="alert block">
	Il y a des écritures non rapprochées avant la date du {$start|date_short}. Le solde rapproché peut donc se révéler erroné.
</p>
{/if}

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
		{if $sum_start}
		<tbody>
			<tr>
				<td colspan="6"></td>
				<td class="money" data-sum-start="{$sum_start}">{$sum_start|raw|money}</td>
				<th>Solde initial du relevé de compte</th>
				<td colspan="2"></td>
			</tr>
		</tbody>
		{/if}
		<tbody class="lines">
			{foreach from=$journal item="line"}
			{if isset($line->sum)}
			<tr>
				<td colspan="5"></td>
				<td class="money" data-sum="{$line.sum}">{if $line.sum > 0}-{/if}{$line.sum|abs|raw|money:false}</td>
				<td class="money" data-reconciled-sum="{$line.reconciled_sum}">{if $line.reconciled_sum > 0}-{/if}{$line.reconciled_sum|abs|raw|money}</td>
				<th>Solde au {$line.date|date_short}</th>
				<td colspan="2"></td>
			</tr>
			{else}
			<tr{if $line.reconciled} class="disabled"{/if}>
				<td class="check">
					{input type="checkbox" name="reconcile[%d]"|args:$line.id_line value="1" default=$line.reconciled onchange="recalculateTable();"}
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
		{if $sum_end}
		<tfoot>
			<tr>
				<td colspan="6"></td>
				<td class="money" data-sum-end="{$sum_end}">{$sum_end|raw|money}</td>
				<th>Solde final du relevé de compte</th>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td colspan="6"></td>
				<td class="money" data-sum-end-diff="{$sum_end_diff}">{$sum_end_diff|raw|money_html:false}</td>
				<th>Différence entre solde théorique et solde final</th>
				<td colspan="2"></td>
			</tr>
		</tfoot>
		{/if}
	</table>
	<p class="submit noprint">
		{csrf_field key="acc_reconcile_%s"|args:$account.id}
		{button type="submit" name="save" label="Enregistrer" class="main" shape="check"}
		{if $next}
			{button type="submit" name="save_next" label="Enregistrer et aller au mois suivant" class="main minor" shape="right"}
		{/if}
	</p>
</form>

<script type="text/javascript" defer="defer">
{literal}

function toggleFilters(elm)
{
	g.toggle('.simple-filters', false);
	g.toggle('.advanced-filters', true);
	elm.remove();
}

function recalculateTable()
{
	var checkboxes = $('tbody.lines input[type=checkbox]');
	var diff = document.querySelector('[data-sum-end-diff]');
	var sum_start = parseInt(document.querySelector('[data-reconciled-sum]').dataset.reconciledSum, 10);
	var sum_end = document.querySelectorAll('[data-reconciled-sum]')[1];
	var sum = sum_start;

	checkboxes.forEach(checkbox => {
		var row = checkbox.parentNode.parentNode;
		var col = row.querySelector('td[data-credit]');
		var change = 0;

		if (checkbox.checked) {
			// Not a bug! Credit/debit is reversed here to reflect the bank statement
			change = parseInt(col.dataset.debit, 10) || -(parseInt(col.dataset.credit, 10));
		}

		sum += change * -1;
		col.innerHTML = g.formatMoney(sum * -1, true);
	});

	sum_end.innerHTML = g.formatMoney(sum * -1, true);

	if (diff) {
		var sum_end = parseInt(document.querySelector('[data-sum-end]').getAttribute('data-sum-end'), 10);
		diff.innerHTML = g.formatMoney(sum_end - (sum * -1));
	}
}
{/literal}
</script>

{include file="_foot.tpl"}