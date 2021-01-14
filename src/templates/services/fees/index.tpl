{include file="admin/_head.tpl" title="%s — Tarifs"|args:$service.label current="membres/services"}

{include file="services/_nav.tpl" current="index" current_service=$service service_page="index"}

{if count($list)}
	<table class="list">
		<thead>
			<th>Tarif</th>
			<td>Montant</td>
			<td>Membres à jour et ayant payé</td>
			<td>Membres expirés</td>
			<td>Membres en attente de règlement</td>
			<td></td>
		</thead>
		<tbody>
			{foreach from=$list item="row"}
				<tr>
					<th><a href="details.php?id={$row.id}">{$row.label}</a></th>
					<td>
						{if $row.formula}
							Formule
						{elseif $row.amount}
							{$row.amount|money_currency|raw}
						{else}
							-
						{/if}
					</td>
					<td class="num"><a href="details.php?id={$row.id}">{$row.nb_users_ok}</a></td>
					<td class="num"><a href="details.php?id={$row.id}&amp;type=expired">{$row.nb_users_expired}</a></td>
					<td class="num"><a href="details.php?id={$row.id}&amp;type=unpaid">{$row.nb_users_unpaid}</td>
					<td class="actions">
						{linkbutton shape="users" label="Liste des inscrits" href="!services/fees/details.php?id=%d"|args:$row.id}
						{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
							{linkbutton shape="edit" label="Modifier" href="!services/fees/edit.php?id=%d"|args:$row.id}
							{linkbutton shape="delete" label="Supprimer" href="!services/fees/delete.php?id=%d"|args:$row.id}
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{else}
	<p class="block alert">
		Il n'y a aucun tarif enregistré. Créez un premier tarif pour l'activité «&nbsp;{$service.label}&nbsp;» pour pouvoir y inscrire des membres.
	</p>
{/if}

{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
	{include file="services/fees/_fee_form.tpl" legend="Ajouter un tarif" submit_label="Ajouter" csrf_key="fee_add" fee=null amount_type=0 account=null}
{/if}

{include file="admin/_foot.tpl"}