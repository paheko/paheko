{include file="admin/_head.tpl" title="Plan comptable" current="compta/categories"}

<ul class="actions">
	<li class="current"><a href="{$admin_url}compta/comptes/">Plan comptable</a></li>
	<li><a href="?import">Import / remise à zéro</a></li>
	<li><a href="?export=plan">Exporter le plan en format JSON</a></li>
</ul>

<ul class="accountList">
{foreach from=$classes item="_classe"}
	<li><h4><a href="{$admin_url}compta/comptes/?classe={$_classe.id}">{$_classe.libelle}</a></h4></li>
{/foreach}
</ul>

{include file="admin/_foot.tpl"}