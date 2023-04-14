{include file="_head.tpl" title="Créer un nouveau module" current="config"}

<p class="help block">
	Les modules permettent aux personnes ayant quelques compétences en programmation de rajouter des fonctionnalités.<br/>
	{linkbutton shape="help" label="Comment modifier et développer des modules" href="!static/doc/modules.html" target="_dialog"}
</p>

{form_errors}

<form method="post" action="">
<fieldset>
	<legend>Informations du module</legend>
	<dl>
		{input type="text" name="label" required="true" label="Nom du module" help="Par exemple « Reçu personnalisé »"}
		{input type="text" name="name" required="true" label="Nom unique du module" pattern="[a-z][a-z0-9]*(_[a-z0-9]+)*" help="Ne peut contenir que des lettres minuscules sans accent, des chiffres, et des tirets bas."}
		{input type="textarea" cols="50" rows="3" name="description" label="Description"}
		{input type="text" name="author" label="Nom de l'auteur⋅e"}
		{input type="url" name="author_url" label="Adresse du site de l'auteur⋅e"}
	</dl>
</fieldset>

<fieldset>
	<legend>Configuration</legend>
	<dl>
		{input type="checkbox" name="menu" label="Afficher dans le menu" value=1}
		{input type="checkbox" name="home_button" label="Afficher un bouton sur l'accueil" value=1}
		{*input type="select" name="web" label="Type de module" options=$types required=true*}
		{input type="select_groups" name="restrict" options=$sections label="Restreindre l'accès aux membres ayant accès à…"}
	</dl>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{button type="submit" shape="right" label="Créer ce module" name="create" class="main"}
</p>
</fieldset>
</form>

<script type="text/javascript">
{literal}
$('#f_label').onkeyup = () => {
	$('#f_name').value = $('#f_label').value.normalize('NFD').replace(/[\u0300-\u036f]/g, "").toLowerCase().replace(/[^a-z0-9_]/g, '_');
};
{/literal}
</script>

{include file="_foot.tpl"}