{include file="_head.tpl" title="Message collectif : %s"|args:$mailing.subject current="users/mailing" hide_title=true}

<nav class="tabs">
	<aside>
		{linkbutton shape="users" label="Destinataires" href="recipients.php?id=%d"|args:$mailing.id}
		{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$mailing.id target="_dialog"}
	{if !$mailing.sent}
		{linkbutton shape="edit" label="Modifier" href="write.php?id=%d"|args:$mailing.id}
		{linkbutton shape="right" label="Envoyer" href="send.php?id=%d"|args:$mailing.id target="_dialog" class="main"}
	{/if}
	</aside>
	<ul>
		<li class="current"><a href="./">Messages collectifs</a></li>
		<li><a href="status/">Statut des envois</a></li>
	</ul>
</nav>

{if $sent}
	<p class="confirm block">L'envoi du message a bien commencé. Il peut prendre quelques minutes avant d'avoir été expédié à tous les destinataires.</p>
{/if}

{form_errors}

<div class="mailing-preview">
	<aside>
		<header>
		</header>
		{if !empty($hints)}
			<div class="alert block">
				<h3>Problèmes potentiels détectés&nbsp;!</h3>
				<ul>
				{foreach from=$hints item="message"}
					<li>{$message}</li>
				{/foreach}
				</ul>
				<h4>Pourquoi corriger&nbsp;?</h4>
				<p>Ces problèmes peuvent mener à ce que le message&nbsp;:</p>
				<ul>
					<li>ne soit pas lu,</li>
					<li>arrive dans le dossier <em>Indésirables</em>,</li>
					<li>ou même à ce qu'il soit complètement bloqué par certains fournisseurs.</li>
				</ul>
				<p>Il est donc recommandé de corriger ces points avant envoi.</p>
			</div>
		{/if}
	</aside>
	<div class="container">
		<header>
			<dl class="describe">
				<dt>Sujet</dt>
				<dd><h2>{$mailing.subject}</h2></dd>
				<dt>Extrait</dt>
				<dd><small>{$mailing->getPreheader()}</small></dd>
				<dt>De</dt>
				<dd>{$mailing->getFrom()}</dd>
				<dt>À</dt>
				<dd><a href="recipients.php?id={$mailing.id}">{{%n destinataire}{%n destinataires} n=$mailing->countRecipients()}</a></dd>
				<dt>Envoyé le</dt>
				<dd>{if $mailing.sent}{$mailing.sent|date_long:true}{else}<em>Brouillon (non envoyé)</em>{/if}</dd>
			</dl>
		</header>
		<div class="preview">
			<ul class="tabs">
				<li class="current">{link href="preview.php?id=%d"|args:$mailing.id label="Ordinateur"}</li>
				<li>{link href="preview.php?id=%d&view=handheld"|args:$mailing.id label="Mobile"}</li>
				<li>{link href="preview.php?id=%d&view=text"|args:$mailing.id label="Texte"}</li>
				<li>{link href="preview.php?id=%d&view=code"|args:$mailing.id label="Code"}</li>
			</ul>
			<iframe src="preview.php?id={$mailing.id}"></iframe>
		</div>
	</div>
</div>

<script type="text/javascript">
{literal}
var iframe = $('.preview iframe')[0];
var tabs = $('.preview .tabs li');
tabs.forEach(li => {
	var a = li.querySelector('a');
	a.onclick = () => {
		tabs.forEach(li => li.className = '');
		iframe.src = a.href;
		a.parentNode.className = 'current';
		return false;
	};
});
{/literal}
</script>

{include file="_foot.tpl"}
