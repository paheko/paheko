<table class="list">
	<thead class="userOrder">
		<tr>
			{foreach from=$list.columns key="key" item="column"}
			<?php if (!isset($column['label'])) { continue; } ?>
			<td class="{if $list->order == $key}cur {if $list->desc}desc{else}asc{/if}{/if}">
				{$column.label}
				<a href="{$list->orderURL($key, false)}" class="icn up">&uarr;</a>
				<a href="{$list->orderURL($key, true)}" class="icn dn">&darr;</a>
			</td>
			{/foreach}
			<td></td>
		</tr>
	</thead>
	<tbody>