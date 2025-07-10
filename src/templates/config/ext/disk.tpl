{include file="_head.tpl" title="%s — Espace disque occupé"|args:$ext.label current="config"}

{include file="config/_menu.tpl" current="ext"}
{include file="./_nav.tpl" current="disk" ext=$ext}

<article class="ext-more">
	<?php
	$data_size = $module->getDataSize();
	$config_size = $module->getConfigSize();
	$code_size = $module->getCodeSize();
	$files_size = $module->getFilesSize();
	$total = $data_size + $code_size + $files_size + $config_size;
	$code_sloc = $module->getCodeSLOC();
	?>
	<table class="list meter-map auto">
		<tr>
			<th scope="row">Total</th>
			<td class="size"><nobr>{$total|size_in_bytes}</nobr></td>
			<td></td>
		</tr>
		<tr height="{$config_size|percent_of:$total}%">
			<th scope="row">Configuration</th>
			<td class="size"><nobr>{$config_size|size_in_bytes}</nobr></td>
			<td class="actions">
				{if !$data_size && $config_size && $module->canDeleteData()}
					{linkbutton shape="delete" label="Supprimer les données" href="delete.php?name=%s&mode=data"|args:$ext.name target="_dialog"}
				{/if}
			</td>
		</tr>
		<tr height="{$data_size|percent_of:$total}%">
			<th scope="row">Données seules</th>
			<td class="size"><nobr>{$data_size|size_in_bytes}</nobr></td>
			<td class="actions">
				{if $data_size}
					{linkbutton href="!config/advanced/sql.php?table=module_data_%s"|args:$ext.name shape="table" label="Voir les données brutes"}<br />
				{/if}
				{if $data_size && $module->canDeleteData()}
					{linkbutton shape="delete" label="Supprimer les données" href="delete.php?name=%s&mode=data"|args:$ext.name target="_dialog"}
				{/if}
			</td>
		</tr>
		<tr height="{$code_size|percent_of:$total}%">
			<th scope="row">Code source</th>
			<td class="size"><nobr>{$code_size|size_in_bytes}</nobr></td>
			<td class="actions">
				{if $code_size && $ext.module->hasDist()}
					{linkbutton label="Supprimer toutes les modifications" href="delete.php?name=%s&mode=reset"|args:$ext.name shape="delete" target="_dialog"}
				{/if}
			</td>
		</tr>
		<tr height="{$files_size|percent_of:$total}%">
			<th scope="row">Fichiers stockés</th>
			<td class="size"><nobr>{$files_size|size_in_bytes}</nobr></td>
			<td class="actions"></td>
		</tr>
		<caption>Utilisation de l'espace disque</caption>
	</table>
	<p class="help">Nombre de lignes de code : {$code_sloc}</p>
</article>

{include file="_foot.tpl"}
