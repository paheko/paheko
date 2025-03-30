{include file="_head.tpl" title="Préférences membres" current="config"}

{include file="config/_menu.tpl" current="users" sub_current=null}

{if isset($_GET['ok']) && !$form->hasErrors()}
	<p class="block confirm">
		La configuration a bien été enregistrée.
	</p>
{/if}


{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Préférences des membres</legend>
		<dl>
			{input type="select" name="default_category" source=$config options=$users_categories required=true label="Catégorie par défaut des nouveaux membres"}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Champs spéciaux de la fiche de membre</legend>
		<dl>
			{input type="select" name="login_field" default=$login_field options=$login_fields_list required=true label="Champ utilisé comme identifiant de connexion" help="Ce champ de la fiche de membre sera utilisé comme identifiant pour se connecter à l'administration de l'association."}
			<dd class="help">Ce champ doit être unique : il ne peut pas y avoir deux membres ayant la même valeur dans ce champ.</dd>
			{input type="list" name="name_fields" required=true label="Champs utilisés pour définir l'identité des membres" help="Ces champs seront utilisés comme identité (nom) du membre dans les e-mails, les fiches, les pages, etc." target="!config/users/field_selector.php" multiple=true default=$name_fields}
			<dd class="help">Il est possible d'utiliser plusieurs champs, par exemple en choisissant les champs <em>Nom</em> et <em>Prénom</em>, l'identité des membres apparaîtra comme <tt>Nom Prénom</tt>.<br />Dans ce cas l'ordre des champs dans l'identité est déterminé selon l'ordre des champs dans la fiche membre.</dd>
		</dl>
	</fieldset>

	{if $has_parents}
	<fieldset>
		<legend>Rattachement de membres</legend>
		<dl>
			{input prefix_label="Dans la liste des membres…" type="checkbox" name="show_parent_column" value=1 source=$config label="Indiquer le nom du membre responsable" default=1}
			{input type="checkbox" name="show_has_children_column" value=1 source=$config label="Indiquer si des membres sont rattachés à ce membre" default=1}
		</dl>
	</fieldset>
	{/if}

	<fieldset>
		<legend>Journal d'audit</legend>
		<p class="help">
			Les actions de création, modification ou suppression dans la base de données peuvent être enregistrées pour chaque membre.
			Cela permet de garder une trace, pour savoir qui à fait quoi.<br />
			Ces actions sont disponibles dans le <a href="{$admin_url}config/advanced/audit.php">journal d'audit global</a>, ou dans le journal d'audit de chaque membre pouvant se connecter, accessible depuis sa fiche de membre.
		</p>

		<dl>
			{input type="select" options=$log_retention_options source=$config name="log_retention" required=true label="Durée de conservation du journal d'audit" help="Les actions qui sont plus anciennes que ce délai seront supprimés."}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Sécurité</legend>
		<dl>
			{input type="select" name="auto_logout" source=$config required=true label="Déconnecter automatiquement les membres inactifs après…" options=$logout_delay_options}
			<dd class="help">
				Permet de déconnecter automatiquement un membre s'il garde la gestion de l'association ouverte, sans interagir.<br />
				Utile par exemple pour éviter de laisser une session ouverte sur un ordinateur partagé.<br />Ce réglage ne s'applique pas aux membres ayant coché la case "Rester connecté⋅e".
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>


{include file="_foot.tpl"}