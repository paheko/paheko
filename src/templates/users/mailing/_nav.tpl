<nav class="tabs">
	{if $current === 'rejected'}
		<aside>
			{exportmenu right=true}
		</aside>
	{elseif $current === 'index'}
		<aside>
			{linkbutton shape="plus" label="Nouveau message" href="edit.php"}
		</aside>
	{elseif $current === 'mailing'}
		<aside>
			{if !$mailing.sent}
				{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$mailing.id}
			{/if}
			{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$mailing.id}
		</aside>
	{/if}

	<ul>
		<li{if $current === 'index' || $current === 'mailing'} class="current"{/if}><a href="./">Messages collectifs</a></li>
		<li{if $current === 'optout'} class="current"{/if}><a href="optout.php">Désinscriptions</a></li>
		<li{if $current === 'rejected'} class="current"{/if}><a href="rejected.php">Adresses rejetées</a></li>
		{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
			<li {if $current === 'queue'}class="current"{/if}>{link href="queue.php" label="File d'envoi"}</li>
		{/if}
	</ul>

	{if $current === 'mailing'}
	<ul class="sub">
		<li class="title">{$mailing.subject}</li>
		<li>{link href="recipients.php?id=%d"|args:$mailing.id label="Destinataires"}</li>
	</ul>
	{/if}
</nav>
