{include file="_head.tpl" title="Partager" current="docs"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>{$file.name}</legend>
		<p class="help">
			Un lien de partage sera créé, permettant de partager ce fichier publiquement, sans avoir à se connecter à la gestion de l'association.
		</p>
	{if $share_url}
		<dl>
			{input type="url" copy=true readonly=true name="" size=100 onclick="this.select();" label="Pour partager ce fichier, transmettez cette adresse :" default=$share_url}
		</dl>
	{else}
		<dl>
			{input type="select" name="expiry" required=true label="Durée de validité du lien" options=$expiry_options default=24*365 help="Après ce délai, le lien ne sera plus valide."}
			{input type="password" name="password" label="Définir un mot de passe" help="Si renseigné, alors la personne devra entrer ce mot de passe pour accéder au fichier partagé"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="share" label="Partager" shape="right" class="main"}
		</p>
	{/if}
	</fieldset>
</form>

<p class="help block">
	Le lien de partage cessera de fonctionner si le fichier est renommé ou déplacé.
</p>

{include file="_foot.tpl"}