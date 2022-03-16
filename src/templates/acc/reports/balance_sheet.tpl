{include file="admin/_head.tpl" title="Bilan" current="acc/years"}

{include file="acc/reports/_header.tpl" current="balance_sheet" title="Bilan" allow_compare=true}

{if $balance.sums.asset != $balance.sums.liability}
	<p class="alert block">
		<strong>Le bilan n'est pas équilibré&nbsp;!</strong><br />
		Vérifiez que vous n'avez pas oublié de reporter des soldes depuis le précédent exercice.
	</p>
{/if}

{include file="acc/reports/_statement.tpl" statement=$balance}

<p class="help">Toutes les écritures sont libellées en {$config.currency}.</p>

{include file="admin/_foot.tpl"}