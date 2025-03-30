{include file="_head.tpl" title="%sBilan"|args:$title current="acc/years"  prefer_landscape=true}

{include file="acc/reports/_header.tpl" current="balance_sheet" title="Bilan" allow_compare=true allow_filter=true}

<p class="help noprint">Le bilan représente une image de votre organisation&nbsp;: <strong>l'actif</strong> étant ce que l'organisation possède comme ressources (immeubles, comptes en banque, outillage, etc.), et <strong>le passif</strong> représente comment l'organisation a obtenu ces ressources (dettes, fonds de réserve, résultat…). En gros&nbsp;: à gauche = ce qu'on a, à droite = comment on l'a obtenu.</p>

{if $balance.sums.asset != $balance.sums.liability}
	<p class="alert block">
		<strong>Le bilan n'est pas équilibré&nbsp;!</strong><br />
		Vérifiez que vous n'avez pas oublié de reporter des soldes depuis le précédent exercice.
	</p>
{/if}

{include file="acc/reports/_statement.tpl" statement=$balance}

<p class="help">Toutes les écritures sont libellées en {$config.currency}.</p>

{include file="_foot.tpl"}