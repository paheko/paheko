{include file="admin/_head.tpl" title="Compte de résultat" current="acc/years"}

{include file="acc/reports/_header.tpl" current="statement" title="Compte de résultat"}

{include file="acc/reports/_statement.tpl" statement=$general caption1="Charges" caption2="Produits"}

{if !empty($volunteering.expense_sum) || !empty($volunteering.revenue_sum)}
	<h2 class="ruler">Contributions en nature</h2>
	{include file="acc/reports/_statement.tpl" statement=$volunteering header=false caption1="Emplois des contributions volontaires en nature" caption2="Contributions volontaires en nature"}
{/if}

<p class="help">Toutes les écritures sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}