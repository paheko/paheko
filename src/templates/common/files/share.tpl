{include file="_head.tpl" title="Partager" current="docs"}

{include file="./_share_nav.tpl" current="share"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>{$file.name}</legend>
	{if $share}
		<aside class="describe">
			<dl class="describe">
				<dt>Autorisation</dt>
				<dd>{$sharing_options[$share.option]}</dd>
				<dt>Mot de passe</dt>
				<dd>{if $share.password}Oui{else}Non{/if}</dd>
				<dt>Expiration</dt>
				<dd>{if $share.expiry}{$share.expiry|relative_date:true}{else}Jamais{/if}</dd>
			</dl>
		</aside>
		<dl class="share">
			<dt>Pour partager ce fichier, transmettez cette adresse :</dt>
			<dd>{input type="url" copy=true readonly=true name="" onclick="this.select();" default=$share->url()}</dd>
		</dl>
	{else}
		<p class="help">
			Un lien de partage sera créé, permettant de partager ce fichier publiquement, sans avoir à se connecter à l'administration.
		</p>
		<dl>
			{input type="select" required=true name="option" options=$sharing_options label="Que pourront faire les personnes qui auront ce lien de partage ?"}
			{input type="select" name="ttl" required=true label="Durée de validité du lien" options=$ttl_options default=$default_ttl help="Après ce délai, le lien cessera de fonctionner."}
			{input type="password" name="password" label="Demander un mot de passe" help="Si renseigné, alors les personnes devront entrer ce mot de passe pour accéder au fichier partagé."}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="share" label="Partager" shape="right" class="main"}
		</p>
	{/if}
	</fieldset>
</form>

{include file="_foot.tpl"}