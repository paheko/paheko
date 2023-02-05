{include file="_head.tpl" title="Paheko - Installation" menu=false}

<p class="help">
	Bienvenue dans Paheko !
	Veuillez remplir les quelques informations suivantes pour terminer
	l'installation.
</p>

{form_errors}

<form method="post" action="{$self_url}">

<fieldset>
	<legend>Informations sur l'association</legend>
	<dl>
		{input type="select" required=true label="Pays (pour la comptabilité)" options=$countries default="FR" help="Ce choix permet de configurer les règles comptables en fonction du pays de l'association." name="country"}
		{input type="text" label="Nom de l'association" required=true name="name"}
	</dl>
</fieldset>

<fieldset>
	<legend>Création du compte administrateur</legend>
	<dl>
		{input type="text" label="Nom et prénom" required=true name="user_name"}
		{input type="email" label="Adresse E-Mail" required=true name="user_email"}
		{include file="users/_password_form.tpl" field="password" required=true}
	</dl>
</fieldset>

{if count($installable)}
<fieldset>
	<legend>Extensions et modules</legend>
	<table class="list">
		<tr>
			<td>
			</td>
			<th>Nom</th>
			<td>Auteur</td>
		</tr>
		{foreach from=$installable key="name" item="data"}
		<tr>
			{if $data.plugin}
				<td class="check">
					{input type="checkbox" name="plugins[%s]"|args:$name value=1}
				</td>
				<td>
					<label for={"f_plugins%s_1"|args:$name}><strong>{$data.plugin.name}</strong></label><br />
					<span class="help">{$data.plugin.description|escape|nl2br}</span>
				</td>
				<td><a href="{$data.plugin.url}" target="_blank">{$data.plugin.author}</a></td>
			{else}
				<td class="check">
					{input type="checkbox" name="modules[%s]"|args:$name value=1}
				</td>
				<td>
					<label for={"f_modules%s_1"|args:$name}><strong>{$data.module.label}</strong></label><br />
					<span class="help">{$data.module.description|escape|nl2br}</span>
				</td>
				<td>Paheko</td>
			{/if}
		</tr>
		{/foreach}
	</table>
</fieldset>
{/if}

<p class="submit">
	{csrf_field key="install"}
	{button type="submit" name="save" label="Terminer l'installation" shape="right" class="main"}
</p>

</form>


{include file="_foot.tpl"}