<?php
assert(is_array($lines));
assert(is_array($analytical_accounts));
assert(!isset($lines_accounts) || is_array($lines_accounts));
?>

<table class="list transaction-lines">
	<thead>
		<tr>
			<th>Compte</th>
			<td>Débit</td>
			<td>Crédit</td>
			<td>Réf. pièce</td>
			<td>Libellé ligne</td>
			{if count($analytical_accounts) > 0}
				<td>Analytique</td>
			{/if}
			<td></td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$lines key="k" item="line"}
		<tr>
			<th>
				{if isset($lines_accounts)}
					{input type="list" target="%sacc/charts/accounts/selector.php?target=all"|args:$admin_url,$chart_id name="lines[account][]" value=$lines_accounts[$k]}
				{else}
					{input type="list" target="%sacc/charts/accounts/selector.php?target=all"|args:$admin_url,$chart_id name="lines[account][]"}
				{/if}
			</th>
			<td>{input type="money" name="lines[debit][]" value=$line.debit size=5}</td>
			<td>{input type="money" name="lines[credit][]" value=$line.credit size=5}</td>
			<td>{input type="text" name="lines[reference][]" value=$line.reference size=10}</td>
			<td>{input type="text" name="lines[label][]" value=$line.label}</td>
			{if count($analytical_accounts) > 0}
				<td>{input value=$line.id_analytical type="select" name="lines[id_analytical][]" options=$analytical_accounts}</td>
			{/if}
			<td>{button label="Enlever la ligne" shape="minus" min="2" name="remove_line"}</td>
		</tr>
	{/foreach}
	</tbody>
	<tfoot>
		<tr>
			<th>Total</th>
			<td>{input type="money" name="debit_total" readonly="readonly" tabindex="-1" }</td>
			<td>{input type="money" name="credit_total" readonly="readonly" tabindex="-1" }</td>
			<td colspan="3" id="lines_message"></td>
			<td>{button label="Ajouter une ligne" shape="plus"}</td>
		</tr>
	</tfoot>
</table>
