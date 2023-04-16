{include file="_head.tpl" title="Démarrer avec Paheko" menu=false}

<p class="help">
	<strong>Bienvenue dans Paheko !</strong><br />
	Veuillez remplir les informations suivantes pour démarrer la gestion de votre association.
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
	</dl>
	{include file="users/_password_form.tpl" field="password" required=true}
</fieldset>

{if count($installable)}
<fieldset>
	<legend>Activer des extensions</legend>
	<p class="help">Les extensions apportent des fonctionnalités supplémentaires, et peuvent être activées selon vos besoins.</p>
	<table class="list">
		<tr>
			<td>
			</td>
			<th>Nom</th>
		</tr>
		{foreach from=$installable key="name" item="data"}
		<tr>
			{if $data.plugin}
				<td class="check">
					{input type="checkbox" name="plugins[%s]"|args:$name value=1}
				</td>
				<td>
					<label for={"f_plugins%s_1"|args:$name}><strong>{$data.plugin.label}</strong><br />
					<small>{$data.plugin.description|escape|nl2br}</small></label>
				</td>
			{else}
				<td class="check">
					{input type="checkbox" name="modules[%s]"|args:$name value=1}
				</td>
				<td>
					<label for={"f_modules%s_1"|args:$name}><strong>{$data.module.label}</strong><br />
					<small>{$data.module.description|escape|nl2br}</small></label>
				</td>
			{/if}
		</tr>
		{/foreach}
	</table>
	<p class="help">Note : il sera ensuite possible d'activer ou désactiver les extensions dans la <strong>Configuration</strong>.</p>
</fieldset>
{/if}

<p class="submit">
	{csrf_field key="install"}
	{button type="submit" name="save" label="Terminer l'installation" shape="right" class="main"}
</p>

</form>


{include file="_foot.tpl"}