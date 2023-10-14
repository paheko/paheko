{include file="_head.tpl" title="Écritures créées par %s"|args:$transaction_creator->name() current="acc/accounts"}

<p class="help">
	De la plus récente à la plus ancienne.
</p>

{include file="acc/reports/_journal.tpl"}

{include file="_foot.tpl"}