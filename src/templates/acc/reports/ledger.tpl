{if !empty($criterias.projects_only)}
	{include file="_head.tpl" title="Grand livre analytique" current="acc/years" prefer_landscape=true}
	{include file="acc/reports/_header.tpl" current="analytical_ledger" title="Grand livre analytique" allow_filter=true}
{else}
	{include file="_head.tpl" title="%sGrand livre"|args:$title current="acc/years" prefer_landscape=true}
	{include file="acc/reports/_header.tpl" current="ledger" title="Grand livre" allow_filter=true}
{/if}

<div class="year-header noprint">
	<button type="button" data-icon="↓" class="icn-btn" id="open_details">Déplier tous les comptes</button>
	<button type="button" data-icon="↑" class="icn-btn" id="close_details">Replier tous les comptes</button>
</div>

{if $table_export}
	<table class="list statement">
		<thead>
			<tr>
				<td></td>
						{if !empty($criterias.projects_only)}
						<td class="num">Compte</td>
						{/if}
				<td>N° pièce</td>
				<td>Réf. ligne</td>
				<td>Date</td>
				<td>Intitulé</td>
				<td class="money">Débit</td>
				<td class="money">Crédit</td>
				<td class="money">Solde</td>
			</tr>
		</thead>
{/if}

{foreach from=$ledger item="account"}

	{if $table_export}
		<thead>
			<tr>
				<th colspan="{if !empty($criterias.projects_only)}9{else}8{/if}"><h2 class="ruler">{if $account.code}{$account.code} — {/if}{$account.label}</h2></th>
			</tr>
		</thead>
	{else}
		<details open="open">
			<summary><h2 class="ruler">
				{if !empty($criterias.projects_only)}
					<?php $link = sprintf('%sacc/reports/trial_balance.php?project=%d&year=%d', $admin_url, $account->id, $account->id_year); ?>
				{elseif !$criterias.project}
					<?php $link = sprintf('%sacc/accounts/journal.php?id=%d&year=%d', $admin_url, $account->id, $account->id_year); ?>
				{else}
					<?php $link = null; ?>
				{/if}
				{if $link}<a href="{$link}">{/if}
					{if $account.code}{$account.code} — {/if}{$account.label}
				{if $link}</a>{/if}
			</h2></summary>

			<table class="list">
				<thead>
					<tr>
						<td></td>
						{if !empty($criterias.projects_only)}
						<td class="num">Compte</td>
						{/if}
						<td>N° pièce</td>
						<td>Réf. ligne</td>
						<td>Date</td>
						<th>Intitulé</th>
						<td class="money">Débit</td>
						<td class="money">Crédit</td>
						<td class="money">Solde</td>
					</tr>
				</thead>
	{/if}

		<tbody>

		{foreach from=$account.lines item="line"}
			<tr>
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
				{if !empty($criterias.projects_only)}
				<td class="num" data-spreadsheet-type="string">{link href="!acc/accounts/journal.php?id=%d&year=%d"|args:$line.id_account,$line.id_year label=$line.account_code}</td>
				{/if}
				<td data-spreadsheet-type="string">{$line.reference}</td>
				<td data-spreadsheet-type="string">{$line.line_reference}</td>
				<td data-spreadsheet-type="date" data-spreadsheet-value="{$line.date|date:'Y-m-d'}">{$line.date|date_short}</td>
				<th data-spreadsheet-type="string">{$line.label}{if $line.line_label} <em>({$line.line_label})</em>{/if}</th>
				<td class="money">{$line.debit|raw|money}</td>
				<td class="money">{$line.credit|raw|money}</td>
				<td class="money">{$line.running_sum|raw|money:false}</td>
			</tr>
		{/foreach}

		</tbody>
		<tfoot>
			<tr>
				<td colspan="{if !empty($criterias.projects_only)}5{else}4{/if}"></td>
				<th>Solde final</th>
				<td class="money">{$account.debit|raw|money}</td>
				<td class="money">{$account.credit|raw|money}</td>
				<td class="money">{$account.sum|raw|money:false}</td>
			</tr>

			{if $table_export && isset($account->all_debit)}
			<tr>
				<td colspan="{if !empty($criterias.projects_only)}5{else}4{/if}"></td>
				<th><strong>Totaux</strong></th>
				<td class="money">{$account.all_debit|raw|money:false}</td>
				<td class="money">{$account.all_credit|raw|money:false}</td>
				<td></td>
			</tr>
			{/if}

		</tfoot>

	{if !$table_export}
		</table>

	</details>
	{/if}

	{if !$table_export && isset($account->all_debit)}
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
					<td class="money">{$account.all_debit|raw|money:false}</td>
					<td class="money">{$account.all_credit|raw|money:false}</td>
					<td></td>
				</tr>
			</tfoot>
		</table>
	{/if}

{/foreach}

{if $table_export}
	</table>
{/if}

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

<p class="help">Toutes les écritures sont libellées en {$config.currency}.</p>

{include file="_foot.tpl"}
