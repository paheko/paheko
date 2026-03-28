{include file="_head.tpl" title="Diagramme du schéma de base de données" current="config"}

{include file="./_nav.tpl" current="diagram"}

<div class="er-diagram">
{foreach from=$tables item="table"}
	<table id="t_{$table.name}">
		<caption>{link href="table.php?name=%s"|args:$table.name label=$table.name}</caption>
		<tbody>
		{foreach from=$table.columns item="column"}
			<tr id="t_{$table.name}_{$column.name}"
				{if $column.fk.table && $column.fk.to}
				data-fk-table="{$column.fk.table}"
				data-fk-column="{$column.fk.to}"
				{/if}>
				<td>{$column.name}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
{/foreach}
</div>

<script type="text/javascript" src="{$admin_url}static/scripts/erdiagram.js"></script>

{include file="_foot.tpl"}