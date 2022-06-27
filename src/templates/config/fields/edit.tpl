<?php
$title = $field->exists() ? 'Modifier un champ' : 'Ajouter un champ';
?>
{include file="admin/_head.tpl" current="config" title=$title}

{include file="config/_menu.tpl" current="fields"}

{form_errors}

<form method="post" action="{$self_url}">
<fieldset>
	<legend>{$title}</legend>
	{if !$field->exists()}
	<p class="help block">Avant de demander une information personnelle à vos membres… en avez-vous vraiment besoin&nbsp;?<br />
		La loi demande à minimiser au strict minimum les données collectées. Pensez également aux risques de sécurité&nbsp;: si vous demandez la date de naissance complète, cela pourrait être utilisé pour de l'usurpation d'identité, il serait donc plus sage de ne demander que le mois et l'année de naissance, si ces données sont nécessaires afin d'avoir l'âge de la personne.
	</p>
	{/if}
	<dl>
	{if !$field->exists()}
		{input type="select" name="type" options=$field::TYPES source=$field label="Type" default="text" help="Il ne sera plus possible de modifier le type une fois le champ créé." required=true}
		{input type="text" name="name" pattern="[a-z](_?[a-z0-9]+)*" label="Nom unique" required=true source=$field help="Ne peut comporter que des lettres minuscules et des tirets bas. Par exemple pour un champ demandant l'adresse, on peut utiliser 'adresse_postale'. Ce nom ne peut plus être modifié ensuite."}
	{else}
		<dd class="help">Le type et le nom unique ne sont pas modifiables.</dd>
		{input type="select" name="type" options=$field::TYPES source=$field label="Type" disabled=true}
		{input type="text" name="name" disabled=true label="Nom unique" source=$field}
	{/if}

		{input type="text" name="label" label="Libellé" required=true source=$field}
		{input type="text" name="help" label="Texte d'aide" help="Apparaîtra dans les formulaires de manière identique à ce texte." source=$field}

		{input type="checkbox" name="required" value=1 label="Champ obligatoire" help="Si coché, une fiche membre ne pourra pas être enregistrée si ce champ n'est pas renseigné." source=$field}

		{input type="text" name="default" source=$field label="Valeur par défaut" help="Si renseigné, le champ aura cette valeur par défaut lors de l'ajout d'un nouveau membre"}

		{input type="checkbox" name="list_table" value=1 label="Afficher dans la liste des membres" source=$field}
	</dl>
</fieldset>

<fieldset class="type-select type-multiple">
	<legend>Options possibles</legend>

	<p class="alert block type-select">Attention renommer ou supprimer une option n'affecte pas ce qui a déjà été enregistré dans les fiches des membres.</p>
	<p class="alert block type-multiple">Attention changer l'ordre des options peut avoir des effets indésirables.</p>

	<dl class="type-multiple type-select options">
		{foreach from=$field.options item="option"}
		<dd>{input type="text" name="options[]" default=$option}</dd>
		{/foreach}
		<dd>{input type="text" name="options[]"}</dd>
	</dl>
</fieldset>

<fieldset>
	<legend>Accès</legend>
	<dl>
		<dt>Le champ est visible…</dt>
		{input type="radio" name="read_access" value=$field::ACCESS_ADMIN label="Seulement aux personnes qui gèrent les membres" source=$field}
		{input type="radio" name="read_access" value=$field::ACCESS_USER label="Au membre lui-même, et aux personnes qui gèrent les membres" source=$field help="Le membre pourra voir cette information en se connectant" default=$field::ACCESS_USER}
		<dd class="help">Attention&nbsp;: conformément à la réglementation (RGPD), quel que soit votre choix, le membre pourra voir le contenu de ce champ en effectuant un export de ses données personnelles s'il a le droit de se connecter.</dd>

		<dt>Le champ peut être modifié…</dt>
		{input type="radio" name="write_access" value=$field::ACCESS_ADMIN label="Par les personnes qui gèrent les membres" source=$field}
		{input type="radio" name="write_access" value=$field::ACCESS_USER label="Par le membre lui-même, et les personnes qui gèrent les membres" source=$field help="Le membre pourra modifier cette information en se connectant" default=$field::ACCESS_USER}
	</dl>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{linkbutton label="Annuler" shape="left" href="./" target="_parent"}
	{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
</p>

</form>

<script type="text/javascript" src="{$admin_url}static/scripts/config_fields.js"></script>

{include file="admin/_foot.tpl"}