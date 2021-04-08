{include file="admin/_head.tpl" title="Ajouter/supprimer des écritures à un projet" current="acc/accounts"}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Déplacer {$count} fichiers vers…</legend>

		<table class="tree-selector list">
			<tbody>
				<?php $last = 0; ?>
				{foreach from=$breadcrumbs item="_title" key="_path"}
				<tr>
					<td class="check">{input type="radio" name="select" value=$_path}</td>
					<th><?=str_repeat('<i>&nbsp;</i>', $last)?> <b class="icn">&rarr;</b>
						<button type="submit" name="current" value="{$_path}">{$_title}</button></th>
					<?php $last = $iteration; ?>
				</tr>
				{/foreach}
				{foreach from=$directories item="dir"}
				<tr>
					<td class="check">{input type="radio" name="select" value=$dir.path}</td>
					<th><?=str_repeat('<i>&nbsp;</i>', $last)?> <b class="icn">&rarr;</b>
						<button type="submit" name="current" value="{$dir.path}">{$dir.name}</button></th>
				</tr>
				{foreachelse}
				<tr>
					<td class="check"></td>
					<th><?=str_repeat('<i>&nbsp;</i>', $last+1)?> <b class="icn">&rarr;</b> <em>Pas de sous-répertoire</em></th>
				</tr>
				{/foreach}
			</tbody>
		</table>

	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="move" label="Déplacer les fichiers" shape="right" class="main"}

		{if isset($extra)}
			{foreach from=$extra key="key" item="value"}
				{if is_array($value)}
					{foreach from=$value key="subkey" item="subvalue"}
						<input type="hidden" name="{$key}[{$subkey}]" value="{$subvalue}" />
					{/foreach}
				{else}
					<input type="hidden" name="{$key}" value="{$value}" />
				{/if}
			{/foreach}
		{/if}
	</p>

</form>

{include file="admin/_foot.tpl"}
