{include file="_head.tpl" title="Message collectif" current="users/mailing" custom_css=["!web/css.php"]}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$self_url}">Rédaction de message</a></li>
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
			{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
			<nav class="menu">
				<b data-icon="↷" class="btn">Exporter la liste des destinataires</b>
				<span>
					{button type="submit" name="export" value="csv" shape="export" label="Export CSV"}
					{button type="submit" name="export" value="ods" shape="export" label="Export LibreOffice"}
					{if CALC_CONVERT_COMMAND}
						{button type="submit" name="export" value="xlsx" shape="export" label="Export Excel"}
					{/if}
				</span>
			</nav>
			{/if}
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
			<dd>{$config.org_name} &lt;{$config.org_email}&gt;</dd>
			<dt><label for="f_target">Destinataires</label></dt>
			<dd>
				{input type="select_groups" name="target" required=true options=$targets}
			</dd>
			<dd class="help">
				Vous pouvez cibler précisément des membres en créant une <a href="{$admin_url}users/search.php">recherche enregistrée</a>.
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


{include file="_foot.tpl"}