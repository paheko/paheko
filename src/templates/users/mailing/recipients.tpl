{include file="_head.tpl" title="Destinataires du message collectif : %s"|args:$mailing.subject current="users/mailing"}

{include file="./_nav.tpl" current="details"}

<p>
	{linkbutton shape="left" label="Retour au message" href="details.php?id=%d"|args:$mailing.id}
	{exportmenu}
</p>

{if $mailing.anonymous}
	<p class="alert block">
		Les informations personnelles des destinataires ont été supprimées automatiquement après un délai de six mois, conformément au RGPD.
	</p>
{else}
	<p class="help">
		Les informations personnelles des destinataires seront supprimées automatiquement après un délai de six mois, conformément au RGPD.
	</p>
{/if}

{form_errors}
<form method="post" action="">
	{include file="common/dynamic_list_head.tpl"}
	{foreach from=$list->iterate() item="r"}
		<tr>
			<td>{$r.email}</td>
			<td>{$r.name}</td>
			<td>
				{if $r.status}
					<span class="error">{$r.status}</span>
				{/if}
			</td>
			<td>
				{if !$mailing.sent}
					{button shape="delete" label="Supprimer" name="delete" value=$r.id type="submit"}
				{/if}
				{if !$mailing.anonymous && $r.email}
					{linkbutton href="details.php?id=%d&preview=%s"|args:$mailing.id:$r.email label="Prévisualiser" shape="eye" target="_dialog"}
				{/if}
				{if $r.status}
					{linkbutton href="rejected.php?hl=%d#e_%1\$d"|args:$r.id_email label="Détails de l'erreur" shape="help"}
				{/if}
			</td>
		</tr>
		{/foreach}
		</tbody>
	</table>
	{csrf_field key=$csrf_key}
	{$list->getHTMLPagination()|raw}
</form>

{include file="_foot.tpl"}