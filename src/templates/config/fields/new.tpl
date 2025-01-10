{include file="_head.tpl" current="config" title="Ajouter un champ à la fiche de membre" current="config"}

{include file="config/_menu.tpl" current="users" sub_current="fields"}

{form_errors}

<p class="help block">Avant de demander une information personnelle à vos membres… en avez-vous vraiment besoin&nbsp;?<br />
	La loi demande à minimiser au strict minimum les données collectées. Pensez également aux risques de sécurité&nbsp;: si vous demandez la date de naissance complète, cela pourrait être utilisé pour de l'usurpation d'identité, il serait donc plus sage de ne demander que le mois et l'année de naissance, si ces données sont nécessaires afin d'avoir l'âge de la personne.
</p>

<form method="post" action="{$self_url}">
<fieldset>
	<legend>Ajouter un nouveau champ</legend>

	<dl>
		{input type="radio-btn" name="preset" value="" label="Champ personnalisé" required=true help="Permet de créer n'importe quel type de champ : texte, nombre, choix multiple, case à cocher, fichier, etc." default=""}
	</dl>

	<h3>Champs prédéfinis&nbsp;:</h3>
	<dl>
		{foreach from=$presets key="key" item="preset"}
			{if !$preset.disabled}
			{input type="radio-btn" name="preset" value=$key label=$preset.label required=true disabled=$preset.disabled help=$preset.install_help}
			{/if}
		{/foreach}
	</dl>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{linkbutton label="Annuler" shape="left" href="./" target="_parent"}
	{button type="submit" name="add" label="Ajouter" shape="right" class="main"}
</p>

</form>

{include file="_foot.tpl"}