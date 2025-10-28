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
		<li{if $current === 'index' || $current === 'mailing'} class="current"{/if}>{link href="!users/email/mailing/" label="Messages collectifs"}</li>
		<li{if $current === 'status'} class="current"{/if}>>{link href="!users/email/status/" label="Statut des envois"}</a></li>
	</ul>

	{if $current === 'mailing'}
	<ul class="sub">
		<li class="title">{$mailing.subject}</li>
		<li>{link href="recipients.php?id=%d"|args:$mailing.id label="Destinataires"}</li>
	</ul>
	{/if}
</nav>
