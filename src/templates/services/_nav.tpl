<nav class="tabs">
	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}services/">Activités et cotisations</a></li>
		<li{if $current == 'save'} class="current"{/if}><a href="{$admin_url}services/save.php">Enregistrer une activité</a></li>
		{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
			<li{if $current == 'reminders'} class="current"{/if}><a href="{$admin_url}services/reminders/">Gestion des rappels automatiques</a></li>
		{/if}
	</ul>
</nav>
