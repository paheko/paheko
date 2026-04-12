{include file="_head.tpl" title=$table current="config"}

{include file="./_nav.tpl" current="tables"}

<div class="center-block">
	<p>
		{linkbutton shape="table" href="table.php?name=%s"|args:$table label="Voir la structure"}
		{exportmenu}
	</p>
</div>

{$list->getHTMLPagination()|raw}

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{foreach from=$row key="key" item="value"}
			<td>
				{if null == $value}
					<em>NULL</em>
				{elseif ($fk = $foreign_keys[$key] ?? null) && $fk.to}
					{link href="?name=%s&only[%s]=%s"|args:$fk.table:$fk.to:$value label=$value class="num"}
				{elseif Utils::is_json($value)}
					<pre>{$value|format_json}</pre>
				{else}
					{$value}
				{/if}
			</td>
			{/foreach}
			<td></td>
		</tr>
	{/foreach}

	</tbody>
</table>

{$list->getHTMLPagination()|raw}

{include file="_foot.tpl"}
