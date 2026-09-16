{include file="_head.tpl" title="Mots de passe pour applications" current="me"}

{if !$dialog}
	{include file="./_nav.tpl" current="security"}
{/if}

{if $login && $password}
	<p class="block confirm">
		Le mot de passe pour application a été créé.
	</p>
	<fieldset>
		<legend>Utilisez les identifiants suivants pour vous connecter</legend>
		<dl>
			{input type="text" readonly="readonly" copy=true default=$login label="Identifiant" name="login"}
			{input type="text" readonly="readonly" copy=true default=$password label="Mot de passe" help="Recopiez ce mot de passe dans votre application, il ne sera plus affiché ensuite." name=""}
		</dl>
	</fieldset>
{elseif $_GET.msg === 'DELETED'}
	<p class="block confirm">
		Le mot de passe a été supprimé.
	</p>
{/if}

{form_errors}

{if count($list)}
	<form method="post" action="{$self_url}">
		<table class="list">
			<thead>
				<tr>
					<th scope="col">Nom</th>
					<td>Dernière activité</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
			{foreach from=$list item="app"}
				<tr>
					<th scope="row">{$app.name}</th>
					<td>{if $app.last_seen}{$app.last_seen|relative_date:true}{else}Jamais{/if}</td>
					<td class="actions">
						{button type="submit" name="delete" value=$app.id label="Supprimer" shape="delete"}
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
		{csrf_field key=$csrf_key}
	</form>
{else}
	<p class="alert block">Il n'y a aucun mot de passe d'application pour votre compte.</p>
{/if}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Créer un nouveau mot de passe pour application</legend>
		<dl>
			{input type="text" required=true name="name" label="Nom de l'application" help="Par exemple : Navigateur de fichiers KDE"}
		</dl>
	</fieldset>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="create" label="Enregistrer" shape="right" class="main"}
	</p>
	<p class="help">Le mot de passe sera affiché après.</p>
</form>

{include file="_foot.tpl"}
