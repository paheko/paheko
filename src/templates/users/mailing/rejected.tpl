{include file="_head.tpl" title="Adresses rejetées" current="users/mailing"}

{include file="./_nav.tpl" current="rejected"}

{if isset($_GET['sent'])}
<p class="confirm block">
	Un message de demande de confirmation a bien été envoyé. Le destinataire doit désormais cliquer sur le lien dans ce message.
</p>
{elseif isset($_GET['forced'])}
<p class="confirm block">
	La file d'attente a été envoyée.
</p>
{/if}

<form method="post" action="">
<p class="help">
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

{if !$list->count()}
	<p class="alert block">Aucune adresse e-mail n'a été rejetée pour le moment. Cette page présentera les adresses e-mail invalides ou qui ont demandé à se désinscrire.</p>
{else}
	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
		<tr{if $_GET.hl == $row.id} class="highlight"{/if} id="e_{$row.id}">
			<th>{link href="!users/details.php?id=%d"|args:$row.user_id label=$row.identity}</th>
			<td>{$row.email}</td>
			<td><b class="error">{$row.status}</b></td>
			<td class="num">{$row.sent_count}</td>
			<td>{$row.fail_log|escape|nl2br}</td>
			<td>{$row.last_sent|date}</td>
			<td>
				{if $row.email && $row.last_sent < $limit_date}
					<?php $email = rawurlencode($row->email); ?>
					{linkbutton target="_dialog" label="Rétablir" href="!users/mailing/verify.php?address=%s"|args:$email shape="check"}
				{/if}
			</td>
		</tr>

		{/foreach}
	</tbody>
	</table>

	{$list->getHTMLPagination()|raw}

	<div class="block help">
		<h3>Statuts possibles d'une adresse e-mail&nbsp;:</h3>
		<dl class="cotisation">
			{*
			<dt>Vérifiée</dt>
			<dd>L'adresse a déjà reçu un message et a été vérifiée manuellement par le destinataire.</dd>
			*}
			<dt>Invalide</dt>
			<dd>L'adresse n'existe pas ou plus. Il n'est pas possible de lui envoyer des messages.</dd>
			<dt>Trop d'erreurs</dt>
			<dd>Le service destinataire a renvoyé une erreur temporaire plus de {$max_fail_count} fois.<br />Cela arrive par exemple si vos messages sont vus comme du spam trop souvent, ou si la boîte mail destinataire est pleine. Cette adresse ne recevra plus de message.</dd>
		</dl>
		<p class="help">
			Il est possible de rétablir la réception de messages pour les adresses invalides après un délai d'un mois, et les adresses désinscrites immédiatement, en cliquant sur le bouton "Rétablir" qui enverra un message de validation à la personne.
		</p>
	</div>

{/if}

{include file="_foot.tpl"}