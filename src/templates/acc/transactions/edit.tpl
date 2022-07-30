{include file="admin/_head.tpl" title="Modification d'une écriture" current="acc/simple"}

{if $has_reconciled_lines}
<p class="alert block">
	Attention, cette écriture contient des lignes qui ont été rapprochées. La modification de cette écriture entraînera la perte du rapprochement.
</p>
{/if}

{include file="./_form.tpl"}

{include file="admin/_foot.tpl"}