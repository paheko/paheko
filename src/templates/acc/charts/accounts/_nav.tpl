{if !$dialog || $dialog !== 'manage'}
<nav class="tabs">
{if $dialog}
	{* JS trick to get back to the original iframe URL! *}
	<aside>
		{if $current != 'new' && !$chart.archived && $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			{linkbutton href="!acc/charts/accounts/new.php?id=%d&%s"|args:$chart.id,$types_arg label="Ajouter un compte" shape="plus"}
		{/if}
		{linkbutton shape="left" label="Retour à la sélection de compte" href="#" onclick="g.reloadParentDialog(); return false;"}
	</aside>

	<ul>
		<li class="title">{$chart.label}</li>
{else}
	<ul>
		<li><a href="{$admin_url}acc/years/">Exercices</a></li>
		<li><a href="{$admin_url}acc/projects/">Projets <em>(compta analytique)</em></a></li>
		<li class="current"><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
	</ul>
	{if !$chart.archived && $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		<aside>{linkbutton href="!acc/charts/accounts/new.php?id=%d&%s"|args:$chart.id,$types_arg label="Ajouter un compte" shape="plus" target=$dialog_target}</aside>
	{/if}
	<ul class="sub">
		<li class="title">{$chart.label}</li>
{/if}

	{if $chart.country}
		<li{if $current == 'favorites'} class="current"{/if}>{link href="!acc/charts/accounts/?id=%d&%s"|args:$chart.id,$types_arg label="Comptes usuels"}</li>
	{/if}
		<li{if $current == 'all'} class="current"{/if}>{link href="!acc/charts/accounts/all.php?id=%d&%s"|args:$chart.id,$types_arg label="Tous les comptes"}</li>
	</ul>
</nav>
{/if}