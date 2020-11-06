<nav class="tabs">
	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}services/">Activités et cotisations</a></li>
		<li{if $current == 'save'} class="current"{/if}><a href="{$admin_url}services/save.php">Enregistrer une activité</a></li>
		{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
			<li{if $current == 'reminders'} class="current"{/if}><a href="{$admin_url}services/reminders/">Gestion des rappels automatiques</a></li>
		{/if}
	</ul>

	{if isset($current_service)}
	<ul class="sub">
		<li class="title">
			{$current_service.label} —
			{if $current_service.duration}
				{$current_service.duration} jours
			{elseif $current_service.start_date}
				du {$current_service.start_date|date_short} au {$current_service.end_date|date_short}
			{else}
				ponctuelle
			{/if}
		</li>
		<li{if $service_page == 'index'} class="current"{/if}><a href="{$admin_url}services/fees/?id={$current_service.id}"><strong>Gestion des tarifs</strong></a></li>
		<li{if $service_page == 'paid'} class="current"{/if}><a href="{$admin_url}services/details.php?id={$current_service.id}">À jour et payés</a></li>
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
		<li{if $fee_page == 'paid'} class="current"{/if}><a href="{$admin_url}services/fees/details.php?id={$current_fee.id}">À jour et payés</a></li>
		<li{if $fee_page == 'expired'} class="current"{/if}><a href="{$admin_url}services/fees/details.php?id={$current_fee.id}&amp;type=expired">Inscription expirée</a></li>
		<li{if $fee_page == 'unpaid'} class="current"{/if}><a href="{$admin_url}services/fees/details.php?id={$current_fee.id}&amp;type=unpaid">En attente de règlement</a></li>
	</ul>
	{/if}

</nav>
