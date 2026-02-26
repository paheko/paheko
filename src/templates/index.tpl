{include file="_head.tpl" title="Bonjour %s !"|args:$logged_user->name() current="home"}

{$banner|raw}

{if !$has_extensions && $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
	<div class="block help">
		<h2>Besoin d'autres fonctionnalités&nbsp;?</h2>
		<p>Paheko dispose d'autres fonctionnalités sous forme d'extensions optionnelles : caisse, carte de membre, reçus fiscaux, notes de frais, réservations, etc.</p>
		<p>{linkbutton href="!config/ext/?install=1" label="Activer des extensions" shape="check"}</p>
	</div>
{/if}

<nav class="tabs">
	<aside>
		{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
			{linkbutton shape="edit" label="Modifier le texte de l'accueil" href="!config/edit_file.php?k=admin_homepage" target="_dialog"}
		{/if}
		{button id="homescreen-btn" label="Installer comme application web" class="hidden" shape="plus"}
	</aside>
	{if $logged_user && $logged_user->exists()}
		<ul>
			<li><a href="{$admin_url}me/">Mes informations personnelles</a></li>
			<li><a href="{$admin_url}me/services.php">Suivi de mes activités et cotisations</a></li>
		</ul>
	{else}
		<div style="clear: both"></div>
	{/if}
</nav>

<aside class="describe">
	<h3>{$config.org_name}</h3>
	{if !empty($config.org_address)}
	<p>
		{$config.org_address|escape|nl2br}
	</p>
	{/if}
	{if !empty($config.org_phone)}
	<p>
		Tél. : <a href="tel:{$config.org_phone}">{$config.org_phone}</a>
	</p>
	{/if}
	{if !empty($config.org_email)}
	<p>
		E-Mail : <a href="mailto:{$config.org_email}">{$config.org_email}</a>
	</p>
	{/if}
	{if $site_url}
	<p>
		Web : <a href="{$site_url}" target="_blank">{$site_url}</a>
	</p>
	{/if}
</aside>

{if !empty($buttons)}
	<nav class="home">
		<ul>
		{foreach from=$buttons item="button"}
			<li>{$button|raw}</li>
		{/foreach}
		</ul>
	</nav>
{/if}

{if $homepage}
	<article class="web-content home-text">
		{$homepage|raw}
	</article>
{/if}

<script type="text/javascript" src="{$admin_url}static/scripts/homescreen.js" defer="defer"></script>

{include file="_foot.tpl"}