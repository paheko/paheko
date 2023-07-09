<nav class="tabs">
	<aside>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE) && $current === 'payments'}
			{linkbutton href="!payments/new.php" shape="plus" label="Nouveau"}
		{/if}
	</aside>

	<ul>
		<li{if $current == 'payments'} class="current"{/if}>{link href="payments.php" label="Paiements"}</li>
		<li{if $current == 'providers'} class="current"{/if}>{link href="providers.php" label="Prestataires"}</li>
	</ul>
</nav>
