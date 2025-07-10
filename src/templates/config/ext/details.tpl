{include file="_head.tpl" title=$ext.label current="config"}

{include file="config/_menu.tpl" current="ext"}
{include file="./_nav.tpl" current="details" ext=$ext}

{if !$ext.enabled}
	<p class="block alert">Cette extension est désactivée.</p>
{/if}

{if isset($_GET.permissions_saved)}
	<p class="block confirm">Les permissions ont été modifiées.</p>
{/if}

<form method="post" action="">
	{include file="./_details.tpl" item=$ext hide_details=true}
	{csrf_field key=$csrf_key}
</form>

<article class="ext-more">
{if $module}
	{if !$module.enabled && $module->canDelete()}
	<p class="actions">
		{linkbutton label="Supprimer ce module" href="delete.php?name=%s&mode=delete"|args:$module.name shape="delete" target="_dialog"}<br />
	</p>
	{/if}
{else}
	{if !$plugin.enabled && $plugin->exists()}
	<p class="actions">
		{linkbutton label="Supprimer les données" href="delete.php?name=%s"|args:$plugin.name shape="delete" target="_dialog"}
	</p>
	{/if}
{/if}
</article>

{if $ext.restrict_section || count($access_details)}
<div class="help block">
	<h3>Comment accéder à cette extension&nbsp;?</h3>

	{if $ext.restrict_section}
		<p>
			<span class="permissions">{display_permissions section=$ext.restrict_section level=$ext.restrict_level}</span>
			Seuls les membres ayant accès à la section
			«&nbsp;<strong><?=Entities\Users\Category::PERMISSIONS[$ext->restrict_section]['label']?></strong>&nbsp;»
			en
			<strong>
			{if $ext.restrict_level === Users\Session::ACCESS_READ}lecture
			{elseif $ext.restrict_level === Users\Session::ACCESS_WRITE}lecture et écriture
			{elseif $ext.restrict_level === Users\Session::ACCESS_ADMIN}administration
			{/if}
			</strong>
			pourront accéder à cette extension.
		</p>
	{/if}

	<ul>
		{if $ext.ini.restrict_details}
			<li>{$ext.ini.restrict_details|escape|nl2br}</li>
		{/if}
		{foreach from=$access_details item="label"}
			<li>{$label|raw}</li>
		{/foreach}
	</ul>

</div>
{/if}

<div class="ext-tech block">
	<h4>Comment fonctionne cette extension&nbsp;?</h4>
{if $ext.module}
	<p>Cette extension est un <strong>module</strong>, elle est donc <strong>modifiable</strong>.</p>
	<p>Un module est composé de code HTML et Brindille, facile à maîtriser et adapter à ses besoins.</p>
	{if $ext.module->hasDist()}
	<p><em>Vous pouvez à tout moment revenir à la version d'origine en cas de problème.</em></p>
	{/if}
	{linkbutton shape="help" label="Comment modifier et développer des modules" href="!static/doc/modules.html" target="_dialog"}
{else}
	<p>Cette extension est un <strong>plugin</strong> installé sur notre serveur.</p>
	{if !ENABLE_TECH_DETAILS}
		<p>Son code n'est pas modifiable par votre organisation pour des raisons de sécurité.</p>
	{else}
		<p>Son code PHP peut être modifié si vous avez accès au serveur et des connaissances en programmation.</p>
		<p>{linkbutton shape="help" href=$url_help_plugins label="Documentation des plugins" target="_blank"}
	{/if}
{/if}
</div>

{include file="_foot.tpl"}