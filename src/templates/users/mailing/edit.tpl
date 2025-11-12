{include file="_head.tpl" title="Modifier le message collectif" current="users/mailing" hide_title=true}

{form_errors}

<form method="post" action="{$self_url}">
<div class="metadata">
	<fieldset>
		<legend>Informations sur le message</legend>
		<dl>
			{input type="text" name="subject" required=true label="Sujet" source=$mailing class="full-width" help="Il est recommandé de ne pas dépasser 60 caractères et 8 mots pour que le message soit lu." maxlength=100}
			{input type="text" name="preheader" required=false label="Extrait" source=$mailing help="Ce texte apparaîtra en dessous ou à côté du sujet dans la liste des messages du destinataire dans certains logiciels, notamment sur mobile.\nLongueur maximale : 80 caractères. Longueur recommandée : 40 caractères." maxlength=80 class="full-width" data-default=$mailing->getPreheader(true)}
			{input type="text" name="sender_name" source=$mailing label="Nom de l'expéditeur" data-default=$mailing->getFromName() maxlength=60}
			{if $forced_sender}
				{input type="email" name="" disabled=true required=true label="Adresse e-mail de l'expéditeur" default=$forced_sender}
				{if MAIL_SENDER_EXPLAIN}
					<dd class="help"><?=MAIL_SENDER_EXPLAIN?></dd>
				{/if}
				{input type="email" name="sender_email" source=$mailing label="Adresse e-mail de réponse" help="Les réponses au message seront envoyées à cette adresse."}
			{else}
				{input type="email" name="sender_email" source=$mailing label="Adresse e-mail de l'expéditeur"}
			{/if}
		</dl>
	</fieldset>

	<div class="preview" aria-hidden="true">
		<h3>Prévisualisation</h3>
		<div class="mobile-preview">
			<div>
				<span class="avatar">
					<?=htmlentities(mb_substr($mailing->getFromName(), 0, 1))?>
				</span>
				<span class="main">
					<span class="date">
						13:12
					</span>
					<span class="name">
						{$mailing->getFromName()}
					</span>
					<span class="subject">
						{$mailing.subject}
					</span>
					<span class="preheader">
						{$mailing->getPreheader()}
					</span>
				</span>
			</div>
		</div>
		<p class="help">Ceci n'est qu'une prévisualisation possible du message dans la boîte de réception des destinataires.</p>
		<p class="help">Le rendu dépendra du logiciel utilisé par les destinataires.</p>
	</div>
</div>
<p class="submit">
	{csrf_field key=$csrf_key}
	{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
</p>

</form>

<script type="text/javascript">
{literal}
$('#f_subject').onkeyup = (e) => $('.mobile-preview .subject')[0].innerText = e.target.value;
$('#f_preheader').onkeyup = (e) => { $('.mobile-preview .preheader')[0].innerText = e.target.value || e.target.dataset.default };
$('#f_sender_name').onkeyup = (e) => { $('.mobile-preview .name')[0].innerText = e.target.value || e.target.dataset.default };
{/literal}
</script>

{include file="_foot.tpl"}