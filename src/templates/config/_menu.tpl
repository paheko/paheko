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

</nav>{/if}