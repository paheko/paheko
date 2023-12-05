{include file="_head.tpl" title="Extension — %s"|args:$ext.label current="config"}

{include file="config/_menu.tpl" current="ext"}
{include file="./_nav.tpl" current=$mode ext=$ext}

{if $mode === 'readme'}
	<article class="web-content">
		{$content|raw|markdown}
	</article>
{elseif $mode === 'disk' && $module}
	<article class="ext-more">
		<?php
		$data_size = $module->getDataSize();
		$code_size = $module->getCodeSize();
		$files_size = $module->getFilesSize();
		$total = $data_size + $code_size + $files_size;
		?>
		<table class="list meter-map auto">
			<tr>
				<th>Total</th>
				<td class="size"><nobr>{$total|size_in_bytes}</nobr></td>
				<td></td>
			</tr>
			<tr height="{$data_size|percent_of:$total}%">
				<th>Données seules</th>
				<td class="size"><nobr>{$data_size|size_in_bytes}</nobr></td>
				<td class="actions">
					{if $data_size}
						{linkbutton href="!config/advanced/sql.php?table=module_data_%s"|args:$ext.name shape="table" label="Voir les données brutes"}<br />
					{/if}
					{if $data_size && $module->canDeleteData()}
						{linkbutton shape="delete" label="Supprimer les données" href="delete.php?module=%s&mode=data"|args:$ext.name target="_dialog"}
					{/if}
				</td>
			</tr>
			<tr height="{$code_size|percent_of:$total}%">
				<th>Code source</th>
				<td class="size"><nobr>{$code_size|size_in_bytes}</nobr></td>
				<td class="actions">
					{if $code_size && $ext.module->hasDist()}
						{linkbutton label="Supprimer toutes les modifications" href="delete.php?module=%s&mode=reset"|args:$ext.name shape="delete" target="_dialog"}
					{/if}
				</td>
			</tr>
			<tr height="{$files_size|percent_of:$total}%">
				<th>Fichiers stockés</th>
				<td class="size"><nobr>{$files_size|size_in_bytes}</nobr></td>
				<td class="actions"></td>
			</tr>
			<caption>Utilisation de l'espace disque</caption>
		</table>
	</article>
{else}
	{if !$ext.enabled}
		<p class="block alert">Cette extension est désactivée.</p>
	{/if}

	<form method="post" action="">
		{include file="./_details.tpl" item=$ext hide_details=true}
		{csrf_field key=$csrf_key}
	</form>

	<article class="ext-more">
	{if $module}
		{if !$module.enabled && $module->canDelete()}
		<p class="actions">
			{linkbutton label="Supprimer ce module" href="delete.php?module=%s&mode=delete"|args:$module.name shape="delete" target="_dialog"}<br />
		</p>
		{/if}
	{else}
		{if !$plugin.enabled && $plugin->exists()}
		<p class="actions">
			{linkbutton label="Supprimer les données" href="delete.php?plugin=%s"|args:$plugin.name shape="delete" target="_dialog"}
		</p>
		{/if}
	{/if}
	</article>

	{if $ext.restrict_section || count($access_details)}
	<div class="help block">
		<h3>Comment accéder à cette extension&nbsp;?</h3>

		{if $ext.restrict_section}
			<p>
				<span class="permissions">{display_permissions section=$ext.restrict_section level=$ext.restrict_level}</span>
				Seuls les membres ayant accès à la section
				«&nbsp;<strong><?=Entities\Users\Category::PERMISSIONS[$ext->restrict_section]['label']?></strong>&nbsp;»
				en
				<strong>
				{if $ext.restrict_level === Users\Session::ACCESS_READ}lecture
				{elseif $ext.restrict_level === Users\Session::ACCESS_WRITE}lecture et écriture
				{elseif $ext.restrict_level === Users\Session::ACCESS_ADMIN}administration
				{/if}
				</strong>
				pourront accéder à cette extension.
			</p>
		{/if}

		<ul>
			{foreach from=$access_details item="label"}
				<li>{$label|raw}</li>
			{/foreach}
		</ul>

	</div>
	{/if}

	<div class="ext-tech block">
		<h4>Comment fonctionne cette extension&nbsp;?</h4>
	{if $ext.module}
		<p>Cette extension est un <strong>module</strong>, elle est donc <strong>modifiable</strong>.</p>
		<p>Un module est composé de code HTML et Brindille, facile à maîtriser et adapter à ses besoins.</p>
		{if $ext.module->hasDist()}
		<p><em>Vous pouvez à tout moment revenir à la version d'origine en cas de problème.</em></p>
		{/if}
		{linkbutton shape="help" label="Comment modifier et développer des modules" href="!static/doc/modules.html" target="_dialog"}
	{else}
		<p>Cette extension est un <strong>plugin</strong> installé sur notre serveur.</p>
		{if !ENABLE_TECH_DETAILS}
			<p>Son code n'est pas modifiable par votre organisation pour des raisons de sécurité.</p>
		{else}
			<p>Son code PHP peut être modifié si vous avez accès au serveur et des connaissances en programmation.</p>
			<p>{linkbutton shape="help" href=$url_help_plugins label="Documentation des plugins" target="_blank"}
		{/if}
	{/if}
	</div>
{/if}

{include file="_foot.tpl"}