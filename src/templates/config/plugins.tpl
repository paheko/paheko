{include file="_head.tpl" title="Extensions" current="config"}

{include file="config/_menu.tpl" current="plugins"}

{form_errors}

{if !empty($delete)}
	<form method="post" action="{$self_url}">

		<fieldset>
			<legend>Désinstaller une extension</legend>
			<h3 class="warning">
				Êtes-vous sûr de vouloir supprimer l'extension «&nbsp;{$plugin.name}&nbsp;» ?
			</h3>
			<p class="block alert">
				<strong>Attention</strong> : cette action est irréversible et effacera toutes les
				données associées à l'extension.
			</p>
		</fieldset>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="delete" label="Désinstaller" shape="delete" class="main"}
		</p>
	</form>
{else}
	{if !empty($list_installed)}
		<table class="list">
			<thead>
				<tr>
					<th>Extension</th>
					<td></td>
					<td>Version installée</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				{foreach from=$list_installed item="plugin"}
				<tr{if $plugin.disabled} class="disabled"{/if}>
					<th>
						<h4>{$plugin.name}</h4>
						<small>{$plugin.description}</small>
					</th>
					{if $plugin.disabled}
					<td colspan="3">
						<span class="alert">Code source du plugin non trouvé dans le répertoire <em>plugins</em>&nbsp;!</span><br />
						Ce plugin ne peut fonctionner ou être désinstallé.
					</td>
					{else}
					<td>
						<a href="{$plugin.url}" onclick="return !window.open(this.href);">{$plugin.auteur}</a>
					</td>
					<td>
						{$plugin.version}
					</td>
					<td class="actions">
						{if !empty($plugin.config)}
							{linkbutton shape="settings" label="Configurer" href="!plugin/%s/config.php"|args:$plugin.id}
						{/if}
						{linkbutton shape="delete" href="!config/plugins.php?delete=%s"|args:$plugin.id label="Désinstaller"}
					</td>
					{/if}
				</tr>
				{/foreach}
			</tbody>
		</table>
	{else}
		<p class="help">
			Aucune extension n'est installée.
			Vous pouvez consulter <a href="{$garradin_website}">le site de Garradin</a> pour obtenir
			des extensions à télécharger.
		</p>
	{/if}

	{if !empty($list_available)}
	<form method="post" action="{$self_url}">

		<fieldset>
			<legend>Extensions à installer</legend>
			<dl>
				{foreach from=$list_available item="plugin" key="id"}
				{input type="radio" name="plugin" value=$id label=$plugin.name help="version %s"|args:$plugin.version}
				<dd>[<a href="{$plugin.url}" onclick="return !window.open(this.href);">{$plugin.author}</a>] {$plugin.description}</dd>
				{/foreach}
			</dl>
		</fieldset>

		<p class="help">
			Attention : installer une extension non officielle peut présenter des risques de sécurité
			et de stabilité.
		</p>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="install" label="Installer" shape="right" class="main"}
		</p>
	</form>
	{/if}
{/if}

{include file="_foot.tpl"}