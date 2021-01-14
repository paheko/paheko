{include file="admin/_head.tpl" title="Journal général" current="acc/years"}

{include file="acc/reports/_header.tpl" current="journal" title="Journal général"}

{include file="acc/reports/_journal.tpl"}

<p class="help">Toutes les écritures sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}