{include file="admin/_head.tpl" title="Grand livre" current="acc/years"}

{include file="acc/reports/_header.tpl" current="ledger" title="Grand livre"}

<div class="year-header noprint">
	<button type="button" data-icon="↓" class="icn-btn" id="open_details">Déplier tous les comptes</button>
	<button type="button" data-icon="↑" class="icn-btn" id="close_details">Replier tous les comptes</button>
</div>

{foreach from=$ledger item="account"}

<details open="open">
	<summary><h2 class="ruler"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$account.id_year}">{$account.code} — {$account.label}</a></h2></summary>

	<table class="list">
		<thead>
			<tr>
				<td></td>
				<td>N° pièce</td>
				<td>Réf. ligne</td>
				<td>Date</td>
				<th>Intitulé</th>
				<td class="money">Débit</td>
				<td class="money">Crédit</td>
				<td class="money">Solde</td>
			</tr>
		</thead>
		<tbody>
		{foreach from=$account.lines item="line"}
			<tr>
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
				<td>{$line.reference}</td>
				<td>{$line.line_reference}</td>
				<td>{$line.date|date_short}</td>
				<th>{$line.label}{if $line.line_label} <em>({$line.line_label})</em>{/if}</th>
				<td class="money">{$line.debit|raw|html_money}</td>
				<td class="money">{$line.credit|raw|html_money}</td>
				<td class="money">{$line.running_sum|raw|html_money:false}</td>
			</tr>
		{/foreach}
		</tbody>
		<tfoot>
			<tr>
				<td colspan="4"></td>
				<th>Solde final</th>
				<td class="money">{$account.debit|raw|html_money}</td>
				<td class="money">{$account.credit|raw|html_money}</td>
				<td class="money">{$account.sum|raw|html_money:false}</td>
			</tr>
		</tfoot>
	</table>

</details>

{if isset($account->all_debit)}
	<table class="list">
		<colgroup>
			<col width="70%" />
			<col width="10%" />
			<col width="10%" />
			<col width="10%" />
		</colgroup>
		<tfoot>
			<tr>
				<td><strong>Totaux</strong></td>
				<td class="money">{$account.all_debit|raw|html_money:false}</td>
				<td class="money">{$account.all_credit|raw|html_money:false}</td>
				<td></td>
			</tr>
		</tfoot>
	</table>
{/if}

{/foreach}

{literal}
<script type="text/javascript">
document.querySelector('#open_details').onclick = () => {
	document.querySelectorAll('details').forEach((e) => {
		e.setAttribute('open', 'open');
	});
};
document.querySelector('#close_details').onclick = () => {
	document.querySelectorAll('details').forEach((e) => {
		e.removeAttribute('open');
	});
};
</script>
{/literal}

<p class="help">Toutes les écritures sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}
