{if !$dialog}
<nav class="tabs">
	<ul>
		<li><a href="{$admin_url}acc/years/">Exercices</a></li>
		<li class="current"><a href="{$admin_url}acc/projects/">Projets <em>(compta analytique)</em></a></li>
		<li><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
	</ul>

	<aside>
	{if $current == 'index'}
		{exportmenu class="menu-btn-right" xlsx=false}
	{/if}
	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		{linkbutton label="Créer un nouveau projet" href="edit.php" shape="plus" target="_dialog"}
	{/if}
	</aside>

	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
	<ul class="sub">
		<li{if $current != 'config'} class="current"{/if}>{link href="!acc/projects/" label="Liste des projets"}</li>
		<li{if $current == 'config'} class="current"{/if}>{link href="!acc/projects/config.php" label="Configuration"}</li>
	</ul>
	{/if}
</nav>
{/if}