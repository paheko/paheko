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
	<p class="alert">Il n'y a aucune activité enregistrée.</p>
{/if}

{if $session->canAccess('membres', Membres::DROIT_ADMIN)}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Ajouter une activité</legend>
		<dl>
			{input name="label" type="text" required=1 label="Libellé"}
			{input name="description" type="textarea" label="Description"}

			<dt><label for="f_periodicite_jours">Période de validité</label></dt>
			{input name="period" type="radio" value="0" label="Pas de période (cotisation ponctuelle)" default=0}
			{input name="period" type="radio" value="1" label="En nombre de jours"}
			<dd class="period_1">
				<dl>
				{input name="duration" type="number" step="1" label="Durée de validité" size="5"}
				</dl>
			</dd>
			{input name="period" type="radio" value="2" label="Période définie (date à date)"}
			<dd class="period_2">
				<dl class="periode_dates">
					{input type="date" name="start_date" label="Date de début"}
					{input type="date" name="end_date" label="Date de fin"}
				</dl>
			</dd>
		</dl>
	</fieldset>

	<p class="help">
		Il sera possible de définir les tarifs applicables à cette activité dans l'écran suivant.
	</p>

	<p class="submit">
		{csrf_field key="service_new"}
		<input type="submit" name="add" value="Ajouter &rarr;" />
	</p>

</form>

<script type="text/javascript">
{literal}
(function () {
	var hide = [];
	if (!$('#f_period_1').checked)
		hide.push('.period_1');

	if (!$('#f_period_2').checked)
		hide.push('.period_2');

	g.toggle(hide, false);

	function togglePeriod()
	{
		g.toggle(['.period_1', '.period_2'], false);

		if (this.checked && this.value == 1)
			g.toggle('.period_1', true);
		else if (this.checked && this.value == 2)
			g.toggle('.period_2', true);
	}

	$('#f_period_0').onchange = togglePeriod;
	$('#f_period_1').onchange = togglePeriod;
	$('#f_period_2').onchange = togglePeriod;
})();
{/literal}
</script>

{/if}

{include file="admin/_foot.tpl"}