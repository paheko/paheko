<nav class="tabs">
{if $dialog}
	{* JS trick to get back to the original iframe URL! *}
	<aside>{linkbutton shape="left" label="Retour à la sélection de compte" href="#" onclick="g.reloadParentDialog(); return false;"}</aside>
	<ul>
{else}
	<ul>

		<li class="current">{link href="!acc/charts/" label="Plans comptables"}</li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		<li>{link href="!acc/charts/import.php" label="Importer un plan comptable"}</li>
		{/if}
	</ul>
	<ul class="sub">
		<li class="title">{$chart.label}</li>
{/if}

		<li{if $current == 'favorites'} class="current"{/if}>{link href="!acc/charts/accounts/?id=%d"|args:$chart.id label="Comptes favoris"}</li>
		<li{if $current == 'all'} class="current"{/if}>{link href="!acc/charts/accounts/all.php?id=%d"|args:$chart.id label="Tous les comptes"}</li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			<li{if $current == 'new'} class="current"{/if}><strong>{link href="!acc/charts/accounts/new.php?id=%d"|args:$chart.id label="Ajouter un compte"}</strong></li>
		{/if}
	</ul>
</nav>