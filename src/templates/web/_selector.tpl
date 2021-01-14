{include file="admin/_head.tpl" title="Choisir la page parent" current="web" body_id="popup" is_popup=true}

<table class="web-tree list">
	<tbody>
		<tr{if !$parent} class="focused"{/if}>
			<td><input type="button" value="Choisir" data-id="0" data-label="Racine du site" /></td>
			<th><h3><a href="?">Racine du site</a></h3></th>
		</tr>
		<?php $last = 1; ?>
		{foreach from=$breadcrumbs item="_title" key="_id"}
		<tr{if $_id == $parent} class="focused"{/if}>
			<td><input type="button" value="Choisir" data-id="{$_id}" data-label="{$_title}" /></td>
			<th><?=str_repeat('<i>&nbsp;</i>', $last)?> <b class="icn">&rarr;</b> <a href="?parent={$_id}">{$_title}</a></th>
			<?php $last = $iteration; ?>
		</tr>
		{/foreach}
		{foreach from=$categories item="cat"}
		<tr{if $cat.id == $parent} class="focused"{/if}>
			<td><input type="button" value="Choisir" data-id="{$cat.id}" data-label="{$cat.title}" /></td>
			<th><?=str_repeat('<i>&nbsp;</i>', $last)?> <b class="icn">&rarr;</b> <a href="?parent={$cat.id}">{$cat.title}</a></th>
		</tr>
		{foreachelse}
		<tr>
			<td></td>
			<th><?=str_repeat('<i>&nbsp;</i>', $last+1)?> <b class="icn">&rarr;</b> <em>Pas de sous-catégorie…</em></th>
		</tr>
		{/foreach}
	</tbody>
</table>

{literal}
<script type="text/javascript">
var buttons = document.querySelectorAll('input');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.g.inputListSelected(e.dataset.id, e.dataset.label);
	};
});
</script>
{/literal}

{include file="admin/_foot.tpl"}