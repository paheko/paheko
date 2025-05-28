<table class="list{if isset($class)} {$class}{/if}">
	<thead class="userOrder">
		<tr>
			{if !empty($check)}
			<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all" title="Tout cocher / décocher"></label></td>
			{/if}
			{foreach from=$list->getHeaderColumns() key="key" item="column"}
			{if empty($disable_user_sort) && (!array_key_exists('select', $column) || !is_null($column['select'])) && !(array_key_exists('order', $column) && null === $column['order'])}
				<th class="{if $list->order == $key}cur{/if}{if $column.header_icon} icon-header{/if}" scope="col">
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

					{if $column.header_icon}
						{icon shape=$column.header_icon title=$column.label}
					{else}
						{$column.label}
					{/if}

					{if !empty($use_buttons)}
						</button>
					{else}
						</a>
					{/if}
				</th>
			{else}
				<th class="{if $column.header_icon} icon-header{/if}" scope="col">
					<span>
					{if $column.header_icon}
						{icon shape=$column.header_icon title=$column.label}
					{else}
						{$column.label}
					{/if}
					</span>
				</th>
			{/if}
			{/foreach}
			<td></td>
		</tr>
	</thead>
	<tbody>