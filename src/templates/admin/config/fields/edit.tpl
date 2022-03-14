<?php
$title = $field->exists() ? 'Modifier un champ' : 'Ajouter un champ';
?>
{include file="admin/_head.tpl" current="config" title=$title}

{include file="admin/config/_menu.tpl" current="fields"}

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
		{input type="select" name="type" options=$field::TYPES source=$field label="Type" help="Il ne sera plus possible de modifier le type une fois le champ créé." required=true}
	{else}
		{input type="select" name="type" options=$field::TYPES source=$field label="Type" help="Il n'est plus possible de modifier le titre." disabled=true}
	{/if}

		{input type="text" name="label" label="Libellé" required=true source=$field}
		{input type="text" name="help" label="Texte d'aide" help="Apparaîtra dans les formulaires comme ce texte." source=$field}

		{input type="checkbox" name="required" value=1 label="Champ obligatoire" help="Si coché, une fiche membre ne pourra pas être enregistrée si ce champ n'est pas renseigné." source=$field}

		{input type="text" name="default" source=$field label="Valeur par défaut" help="Si renseigné, le champ aura cette valeur par défaut lors de l'ajout d'un nouveau membre"}

		<dt>Le champ est visible…</dt>
		{input type="radio" name="read_access" value=$field::ACCESS_ADMIN label="Seulement aux personnes qui gèrent les membres" source=$field}
		{input type="radio" name="read_access" value=$field::ACCESS_USER label="Au membre lui-même, et aux personnes qui gèrent les membres" source=$field help="Le membre pourra voir cette information en se connectant"}

		<dt>Le champ peut être modifié…</dt>
		{input type="radio" name="write_access" value=$field::ACCESS_ADMIN label="Par les personnes qui gèrent les membres" source=$field}
		{input type="radio" name="write_access" value=$field::ACCESS_USER label="Par le membre lui-même, et aux personnes qui gèrent les membres" source=$field help="Le membre pourra modifier cette information en se connectant"}
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

<p class="submit">
	{csrf_field key=$csrf_key}
	{linkbutton label="Annuler les changements" shape="left" href="./"}
	{button type="submit" name="save" label="Enregistrer les changements" shape="right" class="main"}
</p>

</form>

<script type="text/javascript">
{literal}
var addBtn = document.createElement('button');
addBtn.type = "button";
addBtn.dataset.icon = "➕";
addBtn.className = "icn add";
addBtn.title = "Ajouter une option";

var delBtn = document.createElement('button');
delBtn.type = "button";
delBtn.dataset.icon = "➖";
delBtn.className = "icn delete";
delBtn.title = "Enlever cette option";

var options = $('.options dd');

options.forEach((o, i) => {
	if (i == 0) {
		return;
	}

	let btn = delBtn.cloneNode(true);
	btn.onclick = delOption;
	o.appendChild(btn);
});

addPlusButton();

function addOption(e) {
	var options = $('.options dd');
	var target = e.target;
	var new_option = target.parentNode.cloneNode(true);
	new_option.querySelector('input').value = '';

	new_option.querySelectorAll('button').forEach((b) => b.remove());

	var btn = delBtn.cloneNode();
	btn.onclick = delOption;
	new_option.appendChild(btn);

	target.parentNode.parentNode.appendChild(new_option);
	target.remove(); // Remove add button from previous line

	addPlusButton();
}

function delOption(e) {
	var options = $('.options dd');
	if (options.length == 1) {
		return;
	}

	e.target.parentNode.remove();
	addPlusButton();
}

function addPlusButton () {
	var options = $('.options dd');
	var btn = addBtn.cloneNode();
	btn.onclick = addOption;

	if (options.length < 30) {
		let last = options[options.length - 1];

		if (last.querySelector('.add')) {
			return;
		}

		last.appendChild(btn);
	}
}
{/literal}
</script>

{include file="admin/_foot.tpl"}