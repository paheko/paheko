{include file="_head.tpl" title=$title current="web" hide_title=true}

<nav class="tabs">
	<aside>
		<form method="post" action="search.php" target="_dialog" data-disable-progress="1">
			{input type="text" name="q" size=25 placeholder="Rechercher dans le site" title="Rechercher dans le site"}
			{button shape="search" type="submit" title="Rechercher"}
		</form>
		{if !$config.site_disabled}
			{if $page && $page->isOnline()}
				{linkbutton shape="eye" label="Voir sur le site" target="_blank" href=$page->url()}
			{elseif !$page}
				{linkbutton shape="eye" label="Voir sur le site" target="_blank" href=$www_url}
			{/if}
		{/if}
	</aside>
</nav>

{if !$page}
	<nav class="web config">
		{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
			{if $url = $module->config_url()}
				{linkbutton shape="settings" href=$url label="Configurer le thème" target="_dialog"}
			{/if}
			{linkbutton shape="code" href="!config/ext/edit.php?module=%s"|args:$module.name label="Code du site"}
		{/if}
		{if !$page && $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE)}
			{linkbutton shape="check" href="?check=internal" label="Vérifier les liens internes"}
		{/if}
	</nav>
{else}
	<nav class="web breadcrumbs no-clear">
		<ul>
			<li>{link href="!web/" label="Site web"}</li>
			{foreach from=$breadcrumbs key="id" item="b"}
				<li>{link href="!web/?id=%s"|args:$id label=$b.title|truncate:40}</li>
			{/foreach}
		</ul>
		{if $page}
			<small>{linkbutton href="?id=%d"|args:$page.id_parent shape="left" label="Retour à la catégorie parente"}</small>
		{/if}
	</nav>
{/if}

{if !$page && $config.site_disabled && $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
	<p class="block alert">
		Le site public est désactivé.
		{linkbutton shape="settings" href="!config/" label="Activer le site dans la configuration"}
	</p>
{/if}

{if $_GET.check && !$page}
	{if !empty($links_errors)}
		<div class="block alert">
			Des pages contiennent des liens qui mènent à des pages qui n'existent pas&nbsp;:
			<ul>
				{foreach from=$links_errors item="p"}
				<li>{link href="?id=%d"|args:$p.id label=$p.title}</li>
				{/foreach}
			</ul>
		</div>
	{else}
		<p class="block confirm">Aucune erreur n'a été détectée.</p>
	{/if}
{elseif !empty($links_errors)}
	<div class="block alert">
		<p>Cette page contient des liens qui mènent à des pages internes qui n'existent pas ou ont été renommées&nbsp;:</p>
		<table>
			<thead>
				<tr>
					<th>Libellé du lien</th>
					<th>Adresse du lien</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$links_errors key="uri" item="link"}
				<tr>
					<td>{$link}</td>
					<td><tt>{$uri}</tt></td>
				</tr>
				{/foreach}
			</tbody>
		</table>
		<p>Il est conseillé de modifier la page pour corriger les liens.</p>
	</div>
{/if}

{form_errors}

{if $page && $_GET.history === 'list'}
	{include file="./_history.tpl" versions=$page->listVersions()}
{elseif $page && $_GET.history}
	{include file="./_history.tpl" version=$page->getVersion($_GET.history)}
{elseif $page}
	{include file="./_page.tpl" excerpt=$page->isCategory()}
{/if}

{if !$page || (!$_GET.history && $page->isCategory())}
	<div class="web header">
		{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE)}
		<p class="actions">
			{if $page}
				{assign var="label" value="Nouvelle sous-catégorie"}
			{else}
				{assign var="label" value="Nouvelle catégorie"}
			{/if}
			{linkbutton shape="plus" label=$label target="_dialog" href="new.php?type=%d&parent=%d"|args:$type_category:$page.id}
		</p>
		{/if}
		<h2 class="ruler">{if $page}Sous-catégories{else}Catégories{/if}</h2>
	</div>

	{if count($categories)}
		<nav class="web category-list">
			<ul>
			{foreach from=$categories item="p"}
				<li{if !$p->isOnline()} class="draft"{/if}><a href="?id={$p.id}">{icon shape="folder"}{$p.title}</a></li>
			{/foreach}
			</ul>
		</nav>
	{elseif $page}
		<p class="help">Il n'y a aucune sous-catégorie dans cette catégorie.</p>
	{else}
		<p class="help">Il n'y a aucune catégorie.</p>
	{/if}

	{if $drafts->count()}
		<h2 class="ruler">Brouillons</h2>
		{include file="./_list.tpl" list=$drafts}
	{/if}

	<div class="web header">
		{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE)}
		<p class="actions">
			{linkbutton shape="plus" label="Nouvelle page" target="_dialog" href="new.php?type=%d&parent=%d"|args:$type_page,$page.id}
		</p>
		{/if}
		<h2 class="ruler">Pages</h2>
	</div>
	{if $pages->count()}
		{include file="./_list.tpl" list=$pages}
	{else}
		<p class="help">Il n'y a aucune page dans cette catégorie.</p>
	{/if}
{/if}


{include file="_foot.tpl"}