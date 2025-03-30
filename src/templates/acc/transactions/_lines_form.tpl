<?php
assert(is_array($lines));
assert(is_array($projects));
assert(!isset($lines_accounts) || is_array($lines_accounts));
?>

<table class="list transaction-lines">
	<thead>
		<tr>
			<td>Compte</td>
			<td>Débit</td>
			<td>Crédit</td>
			<td>Libellé ligne</td>
			<td>Réf. ligne</td>
			{if count($projects) > 0}
				<td>Projet</td>
			{/if}
			<td></td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$lines key="k" item="line"}
		<tr>
			<td class="account">
				{input type="list" target="!acc/charts/accounts/selector.php?id_year=%d"|args:$transaction.id_year name="lines[account_selector][]" default=$line.account_selector}
			</td>
			<td class="money">{input type="money" name="lines[debit][]" default=$line.debit size=5 readonly=$line.debit_locked}</td>
			<td class="money">{input type="money" name="lines[credit][]" default=$line.credit size=5 readonly=$line.credit_locked}</td>
			<td>{input type="text" name="lines[label][]" default=$line.label class="full-width"}</td>
			<td>{input type="text" name="lines[reference][]" default=$line.reference size=10 class="full-width"}</td>
			{if count($projects) > 0}
				<td>{input default=$line.id_project type="select" name="lines[id_project][]" options=$projects default_empty="— Aucun —"}</td>
			{/if}
			<td>{button label="Enlever" title="Enlever la ligne" shape="minus" min="2" name="remove_line"}</td>
		</tr>
	{/foreach}
	</tbody>
	<tfoot>
		<tr>
			<th>Total</th>
			<td class="money">{input type="money" name="debit_total" readonly="readonly" tabindex="-1" }</td>
			<td class="money">{input type="money" name="credit_total" readonly="readonly" tabindex="-1" }</td>
			<td colspan="{if count($projects) > 0}3{else}2{/if}" id="lines_message"></td>
			<td>{button label="Ajouter" title="Ajouter une ligne" shape="plus"}</td>
		</tr>
	</tfoot>
</table>
