<nav class="tabs">
	{if !empty($url_plugins)}
		<aside>
			{linkbutton shape="help" href=$url_plugins label="Trouver d'autres extensions à installer" target="_blank"}
		</aside>
	{/if}

	{if $ext}
		{if $current === 'edit'}
			<aside>
				{linkbutton shape="help" label="Comment modifier et développer des modules" href="!static/doc/modules.html" target="_dialog"}

				{linkbutton shape="export" label="Exporter ce module" href="?module=%s&export"|args:$module.name}

				{linkmenu label="Ajouter…" shape="plus" right=true}
					{linkbutton shape="upload" label="Depuis mon ordinateur" target="_dialog" href="!common/files/upload.php?p=%s"|args:$parent_path_uri}
					{linkbutton shape="folder" label="Dossier" target="_dialog" href="!docs/new_dir.php?p=%s&no_redir"|args:$parent_path_uri}
					{linkbutton shape="text" label="Fichier texte" target="_dialog" href="!docs/new_file.php?p=%s&ext="|args:$parent_path_uri}
				{/linkmenu}
			</aside>
		{elseif $current === 'details' && $module}
			<aside>
				{linkbutton label="Exporter ce module" href="edit.php?module=%s&export"|args:$module.name shape="export"}
			</aside>
		{/if}
		<ul class="sub">
			<li class="title"><strong>{$ext.label}</strong></li>
			<li{if $current === 'details'} class="current"{/if}><a href="details.php?name={$ext.name}">Détails</a></li>
			<li{if $current === 'permissions'} class="current"{/if}><a href="permissions.php?name={$ext.name}">Permissions</a></li>
			{if $ext.type === 'module'}
				<li{if $current === 'disk'} class="current"{/if}><a href="disk.php?name={$ext.name}">Espace disque</a></li>
				<li{if $current === 'edit'} class="current"{/if}><a href="edit.php?module={$ext.name}">Code source</a></li>
			{/if}
		</ul>
	{else}
		<ul class="sub">
			<li{if $current === 'enabled'} class="current"{/if}><a href="./">Activées</a></li>
			<li{if $current === 'disabled'} class="current"{/if}><a href="./?install=1">Inactives</a></li>
		</ul>
	{/if}
</nav>