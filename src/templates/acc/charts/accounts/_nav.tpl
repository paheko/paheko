<nav class="tabs">
{if $dialog}
	{* JS trick to get back to the original iframe URL! *}
	<aside>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			{linkbutton href="!acc/charts/accounts/new.php?id=%d"|args:$chart.id label="Ajouter un compte" shape="plus"}
		{/if}
		{linkbutton shape="left" label="Retour à la sélection de compte" href="#" onclick="g.reloadParentDialog(); return false;"}
	</aside>
	<ul>
{else}
	<ul>
		<li class="current">{link href="!acc/charts/" label="Plans comptables"}</li>
	</ul>
	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		<aside>{linkbutton href="!acc/charts/accounts/new.php?id=%d"|args:$chart.id label="Ajouter un compte" shape="plus"}</aside>
	{/if}
	<ul class="sub">
		<li class="title">{$chart.label}</li>
{/if}

		<li{if $current == 'favorites'} class="current"{/if}>{link href="!acc/charts/accounts/?id=%d"|args:$chart.id label="Comptes usuels"}</li>
		<li{if $current == 'all'} class="current"{/if}>{link href="!acc/charts/accounts/all.php?id=%d"|args:$chart.id label="Tous les comptes"}</li>
	</ul>
</nav>