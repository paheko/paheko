{include file="_head.tpl" title="%s — Tarifs liés"|args:$year.label current="acc/years"}

{form_errors}

<div class="help block">
	<p>Pour qu'une écriture puisse être créée lors de l'inscription d'un membre à une activité, un tarif doit être lié à un exercice comptable.</p>
	<p>Après la clôture d'un exercice, il faudra donc modifier l'exercice lié à chaque tarif pour pouvoir continuer à y inscrire des membres.</p>
</div>

{if !count($fees)}
	<p class="block alert">Il n'y a aucun tarif lié à cet exercice.</p>
{else}
	<form method="post" action="">
		<table class="list">
			<thead>
				<tr>
					{if count($years)}
						<td class="check"><input type="checkbox" title="Tout cocher / décocher" aria-label="Tout cocher / décocher" id="f_all" /><label for="f_all" title="Tout cocher / décocher"></label></td>
					{/if}
					<th scope="col">Tarif</th>
					<td></td>
				</tr>
			</thead>
			<tbody>
				{foreach from=$fees item="row"}
				<tr>
					{if count($years)}
						<td class="check">{input type="checkbox" name="check[]" value=$row.id}</td>
					{/if}
					<th scope="row">{$row.service_label} — {$row.fee_label}</th>
					<td class="actions">
						{linkbutton shape="users" href="!services/fees/details.php?id=%d"|args:$row.id label="Liste des inscrits"}
						{linkbutton shape="edit" href="!services/fees/edit.php?id=%d"|args:$row.id label="Modifier"}
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>

	{if count($years)}
		<fieldset>
			<legend>Modifier l'exercice des tarifs cochés</legend>
			<dl>
				{input type="select" required=true name="target" options=$years label="Nouvel exercice à lier aux tarifs cochés"}
			</dl>
			<p class="submit">
				{csrf_field key=$csrf_key}
				{button type="submit" name="link" label="Enregistrer" shape="right" class="main"}
			</p>
		</fieldset>
	{/if}
{/if}
</form>

{include file="_foot.tpl"}