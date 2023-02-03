{include file="_head.tpl" title="Modules" current="config"}

{include file="config/_menu.tpl" current="modules"}

<table class="list">
	<tbody>
		{foreach from=$list item="module"}
		<tr>
			<td class="icon">
				{if $url = $module->icon_url()}
				<svg>
					<use xlink:href='{$url}#img' href="{$url}#img"></use>
				</svg>
				{/if}
			</td>
			<td><h3>{$module.label}</h3>{$module.description|escape|nl2br}</td>
			<td class="actions">
				{*{linkbutton label="Modifier" href="edit.php?module=%s"|args:$module.name shape="edit" target="_dialog"}*}
				{if $module->hasConfig() && $module.enabled}
					{linkbutton label="Configurer" href=$module->url($module::CONFIG_TEMPLATE) shape="settings" target="_dialog"}
				{/if}
				{if $module->canDelete()}
					{if $module->hasDist()}
						{linkbutton label="Remettre à zéro" href="delete.php?module=%s"|args:$module.name shape="reset" target="_dialog"}
					{else}
						{linkbutton label="Supprimer" href="delete.php?module=%s"|args:$module.name shape="delete" target="_dialog"}
					{/if}
				{/if}
			</td>
			<td class="actions">
				{if $module.enabled}
					{linkbutton label="Désactiver" shape="eye-off" href="?disable=%s"|args:$module.name}
				{else}
					{linkbutton label="Activer" shape="eye" href="?enable=%s"|args:$module.name}
				{/if}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>

{include file="_foot.tpl"}