{include file="_head.tpl" title="%sJournal général"|args:$title current="acc/years" prefer_landscape=true}

{include file="acc/reports/_header.tpl" current="journal" title="Journal général" allow_filter=true}

{include file="acc/reports/_journal.tpl"}

<p class="help">Toutes les écritures sont libellées en {$config.currency}.</p>

{include file="_foot.tpl"}