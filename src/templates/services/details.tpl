{include file="admin/_head.tpl" title="%s — Liste des membres inscrits"|args:$service.label current="membres/services"}

{include file="services/_nav.tpl" current="index"}

<nav class="tabs">
	<ul class="sub">
		<li>
			{$service.label} —
			{if $service.duration}
				{$service.duration} jours
			{elseif $service.start_date}
				du {$service.start_date|format_sqlite_date_to_french} au {$service.end_date|format_sqlite_date_to_french}
			{else}
				ponctuelle
			{/if}
		</li>
		<li{if $type == 'all'} class="current"{/if}><a href="?id={$service.id}">Tous les inscrits</a></li>
		<li{if $type == 'expired'} class="current"{/if}><a href="?id={$service.id}&amp;type=expired">Inscription expirée</a></li>
		<li{if $type == 'unpaid'} class="current"{/if}><a href="?id={$service.id}&amp;type=unpaid">En attente de règlement</a></li>
	</ul>
</nav>

<dl class="cotisation">
	<dt>Nombre de membres inscrits</dt>
	<dd>
		{$list->count()}
		<em class="help">(N'apparaît ici que l'inscription la plus récente de chaque membre.)</em>
	</dd>
</dl>

<table class="list">
	<thead class="userOrder">
		<tr>
			{foreach from=$list.columns key="key" item="column"}
			<?php if (!isset($column['label'])) { continue; } ?>
			<td class="{if $list->order == $key}cur {if $list->desc}desc{else}asc{/if}{/if}">
				{$column.label}
				<a href="{$list->orderURL($key, false)}" class="icn up">&uarr;</a>
				<a href="{$list->orderURL($key, true)}" class="icn dn">&darr;</a>
			</td>
			{/foreach}
			<td></td>
		</tr>
	</thead>
	<tbody>
{foreach from=$list->iterate() item="row"}
	<tr>
		<th><a href="../membres/fiche.php?id={$row.id_user}">{$row.identity}</a></th>
		<td>
			{if $row.status == 1}
				<b class="confirm">À jour</b>
			{elseif $row.status == -1}
				<b class="error">En retard</b>
			{else}
				Pas d'expiration
			{/if}
		</td>
		<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
		<td>{$row.expiry|format_sqlite_date_to_french}</td>
		<td>{$row.fee}</td>
		<td>{$row.date|format_sqlite_date_to_french}</td>
		<td class="actions">
			{linkbutton shape="user" label="Toutes les activités de ce membre" href="services/user.php?id=%d"|args:$row.id_user}
			{linkbutton shape="alert" label="Rappels envoyés" href="services/reminders/user.php?id=%d"|args:$row.id_user}
		</td>
	</tr>
{/foreach}
	</tbody>
</table>

{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}


{include file="admin/_foot.tpl"}