{include file="admin/_head.tpl" title="Édition : %s"|args:$page.title current="web"}

{form_errors}

{if $show_diff}
	<h3>Modifications entre votre version et la nouvelle version</h3>
	{diff old=$old_content new=$new_content}
{/if}

<form method="post" action="{$self_url}" class="web-edit">

	<fieldset class="wikiMain">
		<legend>Informations générales</legend>
		<dl>
			{input type="text" name="title" source=$page required=true label="Titre"}
			{input type="text" name="uri" source=$page required=true label="Adresse unique URI" help="Utilisée pour désigner l'adresse de la page sur le site. Ne peut comporter que des lettres, des chiffres, des tirets et des tirets bas." pattern="[A-Za-z0-9_-]+"}
			{input type="list" name="parent_id" label="Catégorie" default=$parent target="web/_selector.php?parent=%d"|args:$page.parent_id required=true}
			{input type="datetime" name="date" label="Date" required=true default=$page->file()->created}
			<dt>Statut</dt>
			{input type="radio" name="status" value=$page::STATUS_ONLINE label="En ligne" source=$page}
			{input type="radio" name="status" value=$page::STATUS_DRAFT label="Brouillon" source=$page help="ne sera pas visible sur le site"}
		</dl>
	</fieldset>

	<fieldset class="wikiEncrypt">
		<dl>
			<dt>
				<input type="checkbox" name="encryption" id="f_encryption" {if $encrypted} checked="checked"{/if} value="1" onchange="checkEncryption(this);" />
				<label for="f_encryption">Chiffrer le contenu</label> <i>(facultatif)</i>
			</dt>
			<noscript>
			<dd>Nécessite JavaScript activé pour fonctionner !</dd>
			</noscript>
			<dd>Mot de passe : <i id="encryptPasswordDisplay" title="Chiffrement désactivé">désactivé</i></dd>
			<dd class="help">Le mot de passe n'est ni transmis ni enregistré, vous seul le connaissez,
				il n'est pas possible de retrouver le contenu si vous l'oubliez.</dd>
		</dl>
	</fieldset>


	<fieldset class="wikiText">
		<div class="textEditor">
			{input type="textarea" name="content" cols="70" rows="35" default=$page->raw() required=true}
		</div>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		<input type="hidden" name="editing_started" value="{$editing_started}" />
		{button type="submit" name="save" label="Enregistrer" shape="upload" class="main"}
		{button type="submit" name="cancel" label="Retourner à la liste" shape="reset"}
	</p>

</form>


{include file="admin/_foot.tpl"}