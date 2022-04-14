{include file="admin/_head.tpl" title="Envoyer un message collectif" current="users/mailing"}

{if $sent}
	<p class="block confirm">Votre message a été envoyé.</p>
{/if}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset class="memberMessage">
		<legend>Message</legend>
		<dl>
			<dt>Expéditeur</dt>
			<dd>{$config.org_name} &lt;{$config.org_email}&gt;</dd>
			<dt>Destinataires</dt>
			<dd>
				<select name="target" required="required">
					<option value="all_">Tous les membres (sauf ceux appartenant à une catégorie cachée)</option>
					<optgroup label="Catégorie de membres">
						{foreach from=$categories key="id" item="label"}
						<option value="category_{$id}" {form_field name="target" selected="category_%d"|args:$id}>{$label}</option>
						{/foreach}
					</optgroup>
					<optgroup label="Membres à jour d'une activité">
						{foreach from=$services key="id" item="label"}
						<option value="service_{$id}" {form_field name="target" selected="service_%d"|args:$id}>{$label}</option>
						{/foreach}
					</optgroup>
					<optgroup label="Recherches enregistrées">
						{foreach from=$search_list item="s"}
						<option value="search_{$s.id}" {form_field name="target" selected="search_%d"|args:$s.id}>{$s.label}</option>
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
			{input type="checkbox" name="copy" value=1 label="Recevoir par e-mail une copie du message envoyé"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="send_mailing"}
		{button type="submit" name="send" label="Envoyer" shape="right" class="main"}
	</p>
</form>


{include file="admin/_foot.tpl"}