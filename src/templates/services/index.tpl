{include file="admin/_head.tpl" title="Activités et cotisations" current="membres/services" js=1}

{include file="services/_nav.tpl" current="index"}

{if count($list)}
	<table class="list">
		<thead>
			<th>Cotisation</th>
			<td>Période</td>
			<td>Membres à jour</td>
			<td>Membres expirés</td>
			<td>Membres en attente de règlement</td>
			<td></td>
		</thead>
		<tbody>
			{foreach from=$list item="row"}
				<tr>
					<th><a href="details.php?id={$row.id}">{$row.label}</a></th>
					<td>
						{if $row.duration}
							{$row.duration} jours
						{elseif $row.start_date}
							du {$row.start_date|format_sqlite_date_to_french} au {$row.end_date|format_sqlite_date_to_french}
						{else}
							ponctuelle
						{/if}
					</td>
					<td class="num">{$row.nb_users_ok}</td>
					<td class="num">{$row.nb_users_expired}</td>
					<td class="num">{$row.nb_users_unpaid}</td>
					<td class="actions">
						{linkbutton shape="menu" label="Tarifs" href="services/fees/?id=%d"|args:$row.id}
						{linkbutton shape="users" label="Liste des inscrits" href="services/details.php?id=%d"|args:$row.id}
						{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
							{linkbutton shape="edit" label="Modifier" href="services/edit.php?id=%d"|args:$row.id}
							{linkbutton shape="delete" label="Supprimer" href="services/delete.php?id=%d"|args:$row.id}
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{else}
	<p class="block alert">Il n'y a aucune activité enregistrée.</p>
{/if}

{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
	{include file="services/_service_form.tpl" legend="Ajouter une activité" service=null period=0}
{/if}

{include file="admin/_foot.tpl"}