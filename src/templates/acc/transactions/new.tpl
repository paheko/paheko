{include file="_head.tpl" title="Saisie d'une écriture" current="acc/new"}

{include file="acc/_year_select.tpl"}

{if !empty($duplicate_from)}
<p class="help block">
	Cette saisie est dupliquée depuis l'écriture {link class="num" href="details.php?id=%d"|args:$duplicate_from label="#%d"|args:$duplicate_from}
</p>
{/if}

{if isset($snippets)}
	{$snippets|raw}
{/if}

{include file="./_form.tpl"}

{include file="_foot.tpl"}