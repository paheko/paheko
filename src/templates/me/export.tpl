<!DOCTYPE html>
<html>
<head>
	<title>Données utilisateur</title>
	<style type="text/css">
	{literal}
		table {
			border-collapse: collapse;
		}
		table td, table th {
			border: 1px solid #000;
			text-align: left;
			padding: .5em;
		}
		table thead {
			background: #eee;
		}
	{/literal}
	</style>
</head>

<body>
<h1>Données utilisateur</h1>
<p>Ce document contient une copie de toutes les données détenues sur vous par {$config.org_name}, conformément à la réglementation.</p>

<hr />

<h2>Profil</h2>

{include file="users/_details.tpl" data=$user show_message_button=false context="export"}

<hr />

<h2>Inscriptions aux activités et cotisations</h2>

<table>
	<thead>
		<tr>
		{foreach from=$services_list->getHeaderColumns() key="key" item="column"}
			<th>{$column.label}</th>
		{/foreach}
		</tr>
	</thead>

	<tbody>

	{foreach from=$services_list->iterate() item="row"}
		<tr>
			<th>{$row.label}</th>
			<td>{$row.date|date_short}</td>
			<td>{$row.expiry|date_short}</td>
			<td>{$row.fee}</td>
			<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
			<td>{$row.amount|raw|money_currency}</td>
		</tr>
	{/foreach}

	</tbody>
</table>


</body>
</html>