{include file="_head.tpl" current="config" title="Ajouter un champ aux fiches des membres"}

{include file="config/_menu.tpl" current="fields"}

{form_errors}

<p class="help block">Avant de demander une information personnelle à vos membres… en avez-vous vraiment besoin&nbsp;?<br />
	La loi demande à minimiser au strict minimum les données collectées. Pensez également aux risques de sécurité&nbsp;: si vous demandez la date de naissance complète, cela pourrait être utilisé pour de l'usurpation d'identité, il serait donc plus sage de ne demander que le mois et l'année de naissance, si ces données sont nécessaires afin d'avoir l'âge de la personne.
</p>

<form method="post" action="{$self_url}">
<fieldset>
	<legend>Ajouter un nouveau champ</legend>

	<dl>
		{foreach from=$presets key="key" item="preset"}
			{input type="radio" name="preset" value=$key label=$preset.label required=true disabled=$preset.disabled help=$preset.install_help}
			{if $preset.disabled}
				<p class="help">
					Ce champ nécessite d'avoir déjà ajouté les champs suivants :
					{foreach from=$preset.depends item="depends"}
						{$presets[$depends]->label}
					{/foreach}
				</p>
			{/if}
		{/foreach}
		{input type="radio" name="preset" value="" label="Champ personnalisé" required=true}
	</dl>
</fieldset>

<p class="submit">
	{csrf_field key=$csrf_key}
	{linkbutton label="Annuler" shape="left" href="./" target="_parent"}
	{button type="submit" name="add" label="Ajouter" shape="right" class="main"}
</p>

</form>

{include file="_foot.tpl"}