{include file="_head.tpl" title="API" current="config"}

{include file="config/_menu.tpl" current="advanced" sub_current="api"}

{form_errors}

{if count($list)}
<form method="post" action="">

<p class="actions">
	{csrf_field key=$csrf_key}
	{button name="delete" value=1 type="submit" label="Supprimer l'identifiant sélectionné" shape="delete"}
</p>



<table class="list">
	<thead>
		<tr>
			<td></td>
			<th>Description</th>
			<td>Identifiant</td>
			<td>Accès</td>
			<td>Création</td>
			<td>Dernière utilisation</td>
		</tr>
	</thead>
	<tbody>
		{foreach from=$list item="c"}
		<tr>
			<td class="check">
				{input type="radio" name="id" value=$c.id}
			</td>
			<th>{$c.label}</th>
			<td>{$c.key}</td>
			<td class="help">{$access_levels[$c.access_level]}</td>
			<td>{$c.created|date_short}</td>
			<td>{if $c.last_use}{$c.last_use|date}{else}-{/if}</td>
		</tr>
		{/foreach}
	</tbody>
</table>

</form>
{/if}

<form method="post" action="">
	<fieldset>
		<legend>Créer un nouvel identifiant</legend>
		<p class="help">
			Cet identifiant vous permettra de faire des requêtes vers l'API, pour modifier ou récupérer les informations de votre association.<br />
			{linkbutton shape="help" label="Documentation de l'API" href="%swiki?name=API"|args:$website}
		</p>
		<dl>
			{input type="text" name="label" label="Description" required=true}
			{input type="text" name="key" label="Identifiant" help="Seules les lettres minuscules, chiffres et tirets bas sont acceptés." pattern="[a-z0-9_]+" required=true default=$default_key}
			{input type="text" label="Mot de passe" default=$secret readonly="readonly" help="Ce mot de passe ne sera plus affiché, il est conseillé de le copier/coller et l'enregistrer de votre côté." name="secret" copy=true}
			{input type="select" required=true label="Autorisation d'accès" options=$access_levels name="access_level"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="add" label="Créer" shape="plus" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}