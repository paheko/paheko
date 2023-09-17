<article class="ext-details {if $_GET.focus == $item.name} highlight{/if}" id="{$item.name}">
	<header>
	{if $item.broken_message || $item.missing}
		<div></div>
		<div>
		<h2>{$item.label}</h2>
		{if $item.broken_message}
			<p class="error block">
				<strong>Extension cassée : installation impossible</strong><br />
				Erreur : {$item.broken_message}
			</p>
		{elseif $item.missing}
			<p class="error block">
				{if ENABLE_TECH_DETAILS}
					<strong>Le code source de l'extension "{$item.name}" est absent du dossier des plugins</strong>
				{else}
					<strong>Cette extension n'est pas installée sur ce serveur.</strong>
				{/if}
				<br />
				Il n'est pas possible de la supprimer non plus, le code source est nécessaire pour pouvoir la supprimer.
			</p>
		{/if}
		</div>
	{else}
		<figure class="icon">
		{if $item.icon_url}
			<a href="{$item.details_url}"><svg><use xlink:href='{$item.icon_url}#img' href="{$item.icon_url}#img"></use></svg></a>
		{/if}
		</figure>

		<div class="main">
			<div class="title">
				<h2><a href="{$item.details_url}">{$item.label}</a></h2>
			{if $item.module && $item.module->canDelete()}
				<h4>
					<strong class="tag">{icon shape="edit"} Modifiée</strong>
				</h4>
			{elseif $item.module}
				<h4><span class="tag">Modifiable</span></h4>
			{/if}
			</div>
			<p class="desc">
				{$item.description|escape|nl2br}
			</p>
			<p class="author">
				{if $item.author && $item.author_url}
					Par {link label=$item.author href=$item.author_url target="_blank"}
				{elseif $item.author}
					Par <em>{$item.author}</em>
				{/if}
				{if $item.plugin && $item.plugin.version}— Version {$item.plugin.version}{/if}
			</p>

			<p class="actions">
				{if $item.enabled && $item.url && !$item.web}
					{linkbutton shape="right" label="Ouvrir" href=$item.url}
				{/if}
				{if $item.config_url && $item.enabled}
					{linkbutton label="Configurer" href=$item.config_url shape="settings"}
				{/if}
				{if $item.type === 'module'}
					{linkbutton label="Modifier le code" href="edit.php?module=%s"|args:$item.name shape="edit"}
				{/if}
				{if $item.readme}
					{linkbutton label="Documentation" href="details.php?type=%s&name=%s&readme"|args:$item.type:$item.name shape="help"}
				{/if}
			</p>

		</div>

		<div class="toggle">
			{if $item.enabled && !$item.web}
				{button type="submit" label="Désactiver" shape="eye-off" name="disable[%s]"|args:$item.type value=$item.name}
			{elseif !$item.enabled}
				{button type="submit" label="Activer" shape="eye" name="enable[%s]"|args:$item.type value=$item.name}
			{/if}

			{if empty($hide_details)}
				{linkbutton shape="menu" label="Détails" href=$item.details_url}
				{if $item.restrict_section}
					<figure class="permissions">
						<figcaption>Accès limité</figcaption>
						<span class="permissions">{display_permissions section=$item.restrict_section level=$item.restrict_level}</span>
					</figure>
				{/if}
			{/if}

		</div>

	{/if}
	</header>
</article>