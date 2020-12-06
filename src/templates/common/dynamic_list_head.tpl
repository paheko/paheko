<table class="list">
	<thead class="userOrder">
		<tr>
			{if !empty($check)}
			<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
			{/if}
			{foreach from=$list->getHeaderColumns() key="key" item="column"}
			<td class="{if $list->order == $key}cur {if $list->desc}desc{else}asc{/if}{/if}">
				{$column.label}
				{if !array_key_exists('select', $column) || !is_null($column['select'])}
				<a href="{$list->orderURL($key, false)}" class="icn up">&uarr;</a>
				<a href="{$list->orderURL($key, true)}" class="icn dn">&darr;</a>
				{/if}
			</td>
			{/foreach}
			<td></td>
		</tr>
	</thead>
	<tbody>