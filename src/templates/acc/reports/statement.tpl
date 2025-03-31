{include file="_head.tpl" title="%sCompte de résultat"|args:$title current="acc/years"  prefer_landscape=true}

{include file="acc/reports/_header.tpl" current="statement" title="Compte de résultat" allow_compare=true allow_filter=true}

<p class="help noprint">Le compte de résultat indique les dépenses (charges) et recettes (produits), ainsi que le résultat réalisé.</p>

{include file="acc/reports/_statement.tpl" statement=$general}

{if !empty($volunteering.body_left) || !empty($volunteering.body_right)}
	<h2 class="ruler">Contributions bénévoles en nature</h2>
	{include file="acc/reports/_statement.tpl" statement=$volunteering header=false caption1="Emplois des contributions volontaires en nature" caption2="Contributions volontaires en nature" caption="Contributions bénévoles en nature"}
{/if}

<p class="help">Toutes les écritures sont libellées en {$config.currency}.</p>

{include file="_foot.tpl"}