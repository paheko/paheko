{include file="_head.tpl" title="%s — Tarifs liés"|args:$year.label current="acc/years"}

<table class="list">
	<thead>
		<tr>
			<th scope="col">Tarif</th>
			<td></td>
		</tr>
	</thead>
	<tbody>
		{foreach from=$fees item="row"}
		<tr>
			<th>{$row.service_label} — {$row.fee_label}</th>
			<td class="actions">
				{linkbutton shape="users" href="!services/fees/details.php?id=%d"|args:$row.id label="Liste des inscrits"}
				{linkbutton shape="edit" href="!services/fees/edit.php?id=%d"|args:$row.id label="Modifier"}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>


{include file="_foot.tpl"}