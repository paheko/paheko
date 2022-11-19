<table class="list">
	<thead class="userOrder">
		<tr>
			{if !empty($check)}
			<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
			{/if}
			{foreach from=$list->getHeaderColumns() key="key" item="column"}
			<td class="{if $list->order == $key}cur{/if}">
				{if (!array_key_exists('select', $column) || !is_null($column['select'])) && !(array_key_exists('order', $column) && null === $column['order'])}
					{if !empty($use_buttons)}
						<button type="submit" name="_dl_order" value="{if $list.desc}&lt;{else}&gt;{/if}{$key}" title="{if $list->order == $key}Cliquer pour inverser le tri{else}Cliquer pour trier avec cette colonne{/if}">
					{else}
						<a href="{$list->orderURL($key, $list->order == $key ? !$list->desc : $list->desc)}" title="{if $list->order == $key}Cliquer pour inverser le tri{else}Cliquer pour trier avec cette colonne{/if}">
					{/if}

					{if $list.desc}
						{icon shape="down" class="dn"}
					{else}
						{icon shape="up" class="up"}
					{/if}

					{$column.label}

					{if !empty($use_buttons)}
						</button>
					{else}
						</a>
					{/if}
				{else}
					{$column.label}
				{/if}
			</td>
			{/foreach}
			<td></td>
		</tr>
	</thead>
	<tbody>