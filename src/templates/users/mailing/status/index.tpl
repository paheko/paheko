{include file="_head.tpl" title="Statut des envois" current="users/mailing"}

<nav class="tabs">
	{if isset($list)}
	<aside>
		{exportmenu right=true}
	</aside>
	{/if}
	<ul>
		<li><a href="../">Messages collectifs</a></li>
		<li class="current"><a href="./">Statut des envois</a></li>
	</ul>

	<ul class="sub">
		<li{if $status === 'invalid'} class="current"{/if}><a href="?status=invalid">Adresses invalides</a></li>
		<li{if $status === 'optout'} class="current"{/if}><a href="?status=optout">Désinscriptions</a></li>
		<li{if $status === 'queue'} class="current"{/if}><a href="./?status=queue">Messages en attente</a></li>
	</ul>

	{if $status === 'optout'}
	<ul class="sub">
		<li{if $type === 'mailings'} class="current"{/if}><a href="?status=optout">Messages collectifs</a></li>
		<li{if $type === 'reminders'} class="current"{/if}><a href="?status=optout&amp;type=reminders">Rappels</a></li>
		<li{if $type === 'messages'} class="current"{/if}><a href="?status=optout&amp;type=messages">Messages personnels</a></li>
	</ul>
	{/if}
</nav>

{if $status === 'queue'}

	{if isset($_GET['forced'])}
	<p class="confirm block">
		La file d'attente a été envoyée.
	</p>
	{/if}

	<form method="post" action="">
		<p class="alert block">
			{if !$queue_count}
				Il n'y a aucun message en attente d'envoi.
			{else}
				Il y a {$queue_count} messages dans la file d'attente, ils seront envoyés dans quelques instants.
				{if !USE_CRON && $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
					{button shape="right" label="Forcer l'envoi des messages en attente" type="submit" name="force_queue"}
				{/if}
			{/if}
		</p>
	</form>

{else}

	{if isset($_GET['sent'])}
	<p class="confirm block">
		Un message de demande de confirmation a bien été envoyé. Le destinataire doit désormais cliquer sur le lien dans ce message.
	</p>
	{/if}

	{if !$list->count()}
		<p class="alert block">Aucune adresse e-mail à afficher ici.</p>
	{else}
		{$list->getHTMLPagination()|raw}
		{include file="common/dynamic_list_head.tpl"}

			{foreach from=$list->iterate() item="row"}
			<tr>
				<?php $email = rawurlencode($row->email); ?>
				<th>{link href="!users/details.php?id=%d"|args:$row.user_id label=$row.identity}</th>
				<td>{link href="address.php?address=%s"|args:$row.email args=$email label=$row.email target="_dialog"}</td>
				{if $status === 'invalid'}
				<td>{$row.status}</td>
				{/if}
				<td class="num">{$row.sent_count}</td>
				<td>{$row.last_sent|date}</td>
				<td class="actions">
					{linkbutton href="!users/details.php?id=%d"|args:$row.user_id label="Fiche membre" shape="user"}
					{linkbutton label="Détails" href="address.php?address=%s"|args:$email shape="history" target="_dialog"}
				</td>
			</tr>

			{/foreach}
		</tbody>
		</table>

		{$list->getHTMLPagination()|raw}

	{/if}

{/if}

{include file="_foot.tpl"}