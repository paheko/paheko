{include file="_head.tpl" title="Projet" current="acc/years"}

{include file="./_nav.tpl" current=null}

{form_errors}

<form method="post" action="" data-focus="1">
	<fieldset>
		<legend>{if $project->exists()}Modifier un projet{else}Créer un projet{/if}</legend>
		<dl>
			{input type="text" required=true name="label" label="Libellé du projet" source=$project}
			{input type="text" name="code" label="Code du projet" source=$project help="Utile pour retrouver le projet rapidement. Ne peut contenir que des chiffres et des lettres majuscules." pattern="[0-9A-Z_]+"}
			{input type="textarea" name="description" label="Description du projet" source=$project}
			<dt>Archivage</dt>
			{input type="checkbox" name="archived" label="Archiver ce projet" value=1 source=$project help="Si coché, ce projet ne sera plus proposé dans la sélection de projets lors de la saisie d'une écriture."}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>
</form>

{include file="_foot.tpl"}