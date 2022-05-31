{include file="admin/_head.tpl" title="Envoyer un message collectif" current="membres/message" custom_css=["!web/css.php"]}

<nav class="tabs">
    <ul>
    	<li class="current"><a href="{$self_url}">Envoyer</a></li>
    	<li><a href="emails.php">Adresses rejetées</a></li>
    </ul>
</nav>

{if $sent}
	<p class="block confirm">Votre message a été envoyé.</p>
{/if}

{form_errors}

<form method="post" action="{$self_url_no_qs}">
	{if $preview}
		<fieldset class="mailing">
			<legend>Prévisualisation du message</legend>
			<p class="help">
				Ce message sera envoyé à <strong>{$recipients_count}</strong> destinataires.<br />
				Voici un exemple du message pour un de ces destinataires.
			</p>
			<dl>
				<dt>Expéditeur</dt>
				<dd>{$preview.from}</dd>
				<dt>Destinataire</dt>
				<dd>
					{$preview.to}
				</dd>
				<dt>Sujet</dt>
				<dd>{$preview.subject}</dd>
				<dt>Message</dt>
				<dd class="preview">{$preview.html|raw}</dd>
			</dl>
		</fieldset>

		<p class="submit">
			{input type="hidden" name="subject"}
			{input type="hidden" name="message"}
			{input type="hidden" name="target"}
			{input type="hidden" name="send_copy"}
			{input type="hidden" name="render"}
			{csrf_field key=$csrf_key}
			{button type="submit" name="back" label="Retour à l'édition" shape="left"}
			{button type="submit" name="send" label="Envoyer" shape="right" class="main"}
		</p>

	{else}
	<fieldset class="mailing">
		<legend>Message</legend>
		<dl>
			<dt>Expéditeur</dt>
			<dd>{$config.nom_asso} &lt;{$config.email_asso}&gt;</dd>
			<dt><label for="f_target">Destinataires</label></dt>
			<dd>
				<select name="target" id="f_target" required="required">
					<option value="all_">Tous les membres (sauf ceux appartenant à une catégorie cachée)</option>
					<optgroup label="Catégorie de membres">
						{foreach from=$categories key="id" item="label"}
						<option value="category_{$id}" {form_field name="target" selected="category_%d"|args:$id}>{$label}</option>
						{/foreach}
					</optgroup>
					<optgroup label="Recherches enregistrées">
						{foreach from=$search_list item="s"}
						<option value="search_{$s.id}" {form_field name="target" selected="search_%d"|args:$s.id}>{$s.intitule}</option>
						{/foreach}
					</optgroup>
				</select>
			</dd>
			<dd class="help">
				Vous pouvez cibler précisément des membres en créant une <a href="{$admin_url}membres/recherche.php">recherche enregistrée</a>.
				Les recherches enregistrées apparaîtront dans ce formulaire.
			</dd>
			{input type="text" name="subject" required=true label="Sujet"}
			{input type="textarea" name="message" cols=35 rows=25 required=true label="Message"}
			{input type="checkbox" name="send_copy" value=1 label="Recevoir par e-mail une copie du message envoyé"}
			<dt><label for="f_render">Format de rendu</label></dt>
			<dd>
				{input type="select" name="render" options=$render_formats}
				{linkbutton shape="help" href="!web/_syntax_skriv.html" target="_dialog" label="Aide syntaxe SkrivML"}
				{linkbutton shape="help" href="!web/_syntax_markdown.html" target="_dialog" label="Aide syntaxe MarkDown"}
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="preview" label="Prévisualiser" shape="right" class="main"}
	</p>
	{/if}
</form>


{include file="admin/_foot.tpl"}