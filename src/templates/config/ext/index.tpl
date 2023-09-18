{include file="_head.tpl" title="Extensions" current="config"}

{include file="config/_menu.tpl" current="ext"}
<?php $current = $installable ? 'disabled' : 'enabled'; ?>
{include file="./_nav.tpl" current=$current ext=null}

<p class="help">Les extensions apportent des fonctionnalités supplémentaires, et peuvent être activées selon vos besoins.</p>

{form_errors}

<form method="post" action="">
	<section class="ext-list">
	{foreach from=$list item="item"}
		{include file="./_details.tpl" item=$item}
	{/foreach}
	</section>
	{csrf_field key=$csrf_key}
</form>

<p class="help">
	La mention <em class="tag">Modifiable</em> indique que cette extension est un module que vous pouvez modifier.
</p>

<p>
	{linkbutton shape="help" label="Comment modifier et développer des modules" href="!static/doc/modules.html" target="_dialog"}
	{linkbutton shape="plus" label="Créer un module" href="new.php" target="_dialog"}
	{linkbutton shape="import" label="Importer un module" href="import.php" target="_dialog"}
</p>


{include file="_foot.tpl"}