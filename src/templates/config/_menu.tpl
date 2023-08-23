{if !$dialog}
<nav class="tabs">
	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}config/">Configuration</a></li>
		<li{if $current == 'custom'} class="current"{/if}><a href="{$admin_url}config/custom.php">Personnalisation</a></li>
		<li{if $current == 'users'} class="current"{/if}><a href="{$admin_url}config/users/">Membres</a></li>
		<li{if $current == 'backup'} class="current"{/if}><a href="{$admin_url}config/backup/">Sauvegardes</a></li>
		<li{if $current == 'ext'} class="current"{/if}><a href="{$admin_url}config/ext/">Extensions</a></li>
		<li{if $current == 'advanced'} class="current"{/if}><a href="{$admin_url}config/advanced/">Fonctions avancées</a></li>
	</ul>

	{if $current == 'users'}
		{if $sub_current == 'fields'}
			<aside>{linkbutton shape="plus" label="Ajouter un champ" href="new.php"}</aside>
		{/if}

		<ul class="sub">
			<li{if !$sub_current} class="current"{/if}><a href="{$admin_url}config/users/">Préférences</a></li>
			<li{if $sub_current == 'fields'} class="current"{/if}><a href="{$admin_url}config/fields/">Fiche des membres</a></li>
			<li{if $sub_current == 'categories'} class="current"{/if}><a href="{$admin_url}config/categories/">Catégories &amp; droits des membres</a></li>
		</ul>
	{/if}

	{if $current == 'advanced'}
		{if $sub_current == 'sql'}
		<aside>
			{linkbutton shape="check" label="Vérifier la BDD" href="?pragma=integrity_check"}
			{linkbutton shape="check" label="Vérifier les clés étrangères" href="?pragma=foreign_key_check"}
			{if ENABLE_TECH_DETAILS}
				{linkbutton shape="reload" label="Reconstruire" href="?pragma=vacuum"}
			{/if}
		</aside>
		{/if}
	<ul class="sub">
		<li{if !$sub_current} class="current"{/if}><a href="{$admin_url}config/advanced/">Fonctions avancées</a></li>
		<li{if $sub_current == 'audit'} class="current"{/if}>{link href="!config/advanced/audit.php" label="Journal d'audit"}</li>
		<li{if $sub_current == 'api'} class="current"{/if}><a href="{$admin_url}config/advanced/api.php">API</a></li>
		<li{if $sub_current == 'sql'} class="current"{/if}><a href="{$admin_url}config/advanced/sql.php">SQL</a></li>
		{if ENABLE_TECH_DETAILS}
		<li{if $sub_current == 'errors'} class="current"{/if}><a href="{$admin_url}config/advanced/errors.php">Journal d'erreurs</a></li>
		{if SQL_DEBUG}
		<li{if $sub_current == 'sql_debug'} class="current"{/if}><a href="{$admin_url}config/advanced/sql_debug.php">Journal SQL</a></li>
		{/if}
		{/if}
	</ul>
	{/if}
</nav>{/if}