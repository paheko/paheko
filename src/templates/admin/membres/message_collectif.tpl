{include file="admin/_head.tpl" title="Envoyer un message collectif" current="membres/message"}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset class="memberMessage">
		<legend>Message</legend>
		<dl>
			<dt>Expéditeur</dt>
			<dd>{$config.nom_asso} &lt;{$config.email_asso}&gt;</dd>
			<dt>Destinataires</dt>
			<dd>
				<select name="recipients">
					<optgroup label="Catégorie de membres">
						{foreach from=$categories key="id" item="nom"}
						<option value="categorie_{$id}" {form_field name="recipients" selected="categorie_%d"|args:$id}>{$nom}</option>
						{/foreach}
					</optgroup>
					<optgroup label="Recherche de membres">
						{foreach from=$recherches item="r"}
						<option value="recherche_{$r.id}" {form_field name="recipients" selected="recherche_%d"|args:$r.qid}>{$r.intitule}</option>
						{/foreach}
					</optgroup>
				</select>
			</dd>
			{* FIXME : pas encore possible, en attente de refonte gestion cotisations
			<dd>
				<label><input type="checkbox" name="paid_members_only" value="1" {form_field name="paid_members_only" checked=1 default=1} />
					Seulement les membres à jour de cotisation
				</label>
			</dd>
			*}
			<dt><label for="f_sujet">Sujet</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd><input type="text" name="sujet" id="f_sujet" value="{form_field name=sujet}" required="required" /></dd>
			<dt><label for="f_message">Message</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd><textarea name="message" id="f_message" cols="35" rows="25" required="required">{form_field name=message}</textarea></dd>
			<dd>
				<input type="checkbox" name="copie" id="f_copie" value="1" />
				<label for="f_copie">Recevoir par e-mail une copie du message envoyé</label>
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="send_message_co"}
		{button type="submit" name="send" label="Envoyer" shape="right" class="main"}
	</p>
</form>


{include file="admin/_foot.tpl"}