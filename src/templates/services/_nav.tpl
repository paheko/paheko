{if !$dialog}
<nav class="tabs">
	<aside>
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE) && $current != 'reminders'}
			{linkbutton href="!services/user/add.php" label="Inscrire à une activité" shape="plus"}
		{elseif $current == 'reminders'}
			{linkbutton href="!services/reminders/new.php" label="Nouveau rappel automatique" shape="plus" target="_dialog"}
		{/if}
	</aside>

	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}services/">Activités et cotisations</a></li>
		{if !DISABLE_EMAIL && $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
			<li{if $current == 'import'} class="current"{/if}><a href="{$admin_url}services/import.php">Import</a></li>
			<li{if $current == 'reminders'} class="current"{/if}><a href="{$admin_url}services/reminders/">Gestion des rappels automatiques</a></li>
		{/if}
	</ul>

	{if !empty($has_old_services)}
	<ul class="sub">
		<li{if !$show_old_services} class="current"{/if}>{link href="!services/" label="Activités courantes"}</li>
		<li{if $show_old_services} class="current"{/if}>{link href="!services/?old=1" label="Activités passées"}</li>
	</ul>
	{/if}

	{if isset($current_service)}
	<ul class="sub">
		<li class="title">
			{$current_service->long_label()}
		</li>
		<li{if $service_page == 'index'} class="current"{/if}><a href="{$admin_url}services/fees/?id={$current_service.id}"><strong>Tarifs</strong></a></li>
		<li{if $service_page == 'all'} class="current"{/if}><a href="{$admin_url}services/details.php?id={$current_service.id}">Tous les inscrits</a></li>
		<li{if $service_page == 'active'} class="current"{/if}><a href="{$admin_url}services/details.php?id={$current_service.id}&amp;type=active">À jour</a></li>
		<li{if $service_page == 'expired'} class="current"{/if}><a href="{$admin_url}services/details.php?id={$current_service.id}&amp;type=expired">Inscription expirée</a></li>
		<li{if $service_page == 'unpaid'} class="current"{/if}><a href="{$admin_url}services/details.php?id={$current_service.id}&amp;type=unpaid">En attente de règlement</a></li>
	</ul>
	{/if}

	{if isset($current_fee)}
	<ul class="sub">
		<li class="title">
			{$current_fee.label}
			{if $current_fee.amount} — {$current_fee.amount|money_currency|raw}{/if}
		</li>
		<li{if $fee_page == 'all'} class="current"{/if}><a href="{$admin_url}services/fees/details.php?id={$current_fee.id}">Tous les inscrits</a></li>
		<li{if $fee_page == 'active'} class="current"{/if}><a href="{$admin_url}services/fees/details.php?id={$current_fee.id}&amp;type=active">À jour</a></li>
		<li{if $fee_page == 'expired'} class="current"{/if}><a href="{$admin_url}services/fees/details.php?id={$current_fee.id}&amp;type=expired">Inscription expirée</a></li>
		<li{if $fee_page == 'unpaid'} class="current"{/if}><a href="{$admin_url}services/fees/details.php?id={$current_fee.id}&amp;type=unpaid">En attente de règlement</a></li>
	</ul>
	{/if}

</nav>
{/if}