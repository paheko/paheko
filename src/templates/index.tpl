{include file="_head.tpl" title="Bonjour %s !"|args:$logged_user->name() current="home"}

{$banner|raw}

<nav class="tabs">
	<ul>
		<li><a href="{$admin_url}me/">Mes informations personnelles</a></li>
		<li><a href="{$admin_url}me/services.php">Suivi de mes activités et cotisations</a></li>
	</ul>
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
	{if !empty($config.org_web)}
	<p>
		Web : <a href="{$config.org_web}" target="_blank">{$config.org_web}</a>
	</p>
	{/if}
</aside>

<nav class="home">
	<ul>
		<li>{button id="homescreen-btn" label="Installer comme application sur l'écran d'accueil" class="hidden" shape="plus"}</li>
	{foreach from=$buttons item="button"}
		<li>{$button|raw}</li>
	{/foreach}
	</ul>
</nav>

{if $homepage}
	<article class="web-content">
		{$homepage|raw}
	</article>
{/if}

<script type="text/javascript" src="{$admin_url}static/scripts/homescreen.js" defer="defer"></script>

{include file="_foot.tpl"}