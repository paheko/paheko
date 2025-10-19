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
		<li{if !$status} class="current"{/if}><a href="./">Messages en attente</a></li>
		<li{if $status === 'invalid'} class="current"{/if}><a href="?status=invalid">Adresses invalides</a></li>
		<li{if $status === 'optout'} class="current"{/if}><a href="?status=optout">Désinscriptions</a></li>
	</ul>

	{if $status === 'optout'}
	<ul class="sub">
		<li{if $type === 'mailings'} class="current"{/if}><a href="?status=optout">Messages collectifs</a></li>
		<li{if $type === 'reminders'} class="current"{/if}><a href="?status=optout&amp;type=reminders">Rappels</a></li>
		<li{if $type === 'messages'} class="current"{/if}><a href="?status=optout&amp;type=messages">Messages personnels</a></li>
	</ul>
	{/if}
</nav>

{if $status}
	{if isset($_GET['sent'])}
	<p class="confirm block">
		Un message de demande de confirmation a bien été envoyé. Le destinataire doit désormais cliquer sur le lien dans ce message.
	</p>
	{/if}

	<p class="help">
		Seules les adresses e-mail actuellement présentes dans une fiche de membre sont affichées ici.
	</p>

	{if $status === 'invalid'}
		<div class="block help">
			<h3>Statuts possibles d'une adresse e-mail&nbsp;:</h3>
			<dl class="cotisation">
				<dt>Invalide</dt>
				<dd>L'adresse n'existe pas ou plus. Il n'est pas possible de lui envoyer des messages.</dd>
				<dt>Trop d'erreurs</dt>
				<dd>Le service destinataire a renvoyé une erreur temporaire plus de {$max_fail_count} fois.<br />Cela arrive par exemple si vos messages sont vus comme du spam trop souvent, ou si la boîte mail destinataire est pleine. Cette adresse ne recevra plus de message.</dd>
			</dl>
			<p class="help">
				Il est possible de rétablir la réception de messages après un délai de 15 jours en cliquant sur le bouton "Rétablir" qui enverra un message de validation à la personne.
			</p>
		</div>
	{/if}

	{if !$list->count()}
		<p class="alert block">Aucune adresse e-mail à afficher ici.</p>
	{else}
		{$list->getHTMLPagination()|raw}
		{include file="common/dynamic_list_head.tpl"}

			{foreach from=$list->iterate() item="row"}
			<tr{if $_GET.hl == $row.id} class="highlight"{/if} id="e_{$row.id}">
				<th>{$row.id} {link href="!users/details.php?id=%d"|args:$row.user_id label=$row.identity}</th>
				<td>{$row.email}</td>
				{if $status === 'invalid'}
				<td>{$row.status}</td>
				{/if}
				<td class="num">{$row.sent_count}</td>
				<td>{$row.fail_log|escape|nl2br}</td>
				<td>{$row.last_sent|date}</td>
				<td>
					<?php $email = rawurlencode($row->email); ?>
					{if $status === 'optout'}
						{linkbutton target="_dialog" label="Préférences d'envoi" href="preferences.php?address=%s"|args:$email shape="settings"}
					{elseif $row.email && ($row.optout || $row.last_sent < $limit_date)}
						{linkbutton target="_dialog" label="Rétablir" href="verify.php?address=%s"|args:$email shape="check"}
					{/if}
				</td>
			</tr>

			{/foreach}
		</tbody>
		</table>

		{$list->getHTMLPagination()|raw}

	{/if}

{else}
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

{/if}

{include file="_foot.tpl"}