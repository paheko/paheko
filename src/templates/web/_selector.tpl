{include file="_head.tpl" title="Sélectionner la catégorie" current="web"}

<form method="post" action="{$self_url}" class="dir-picker">

	<nav class="breadcrumbs">
		<ul>
		{foreach from=$breadcrumbs item="page"}
			<li class="{if $page.id == $current_cat_id}current{/if}">{button label=$page.title type="submit" name="current" value=$page.id}</li>
		{/foreach}
		</ul>
	</nav>

	<nav class="folders">
		<ul>
		{if $current_cat_id}
			<li class="parent">
				{button shape="left" label="Catégorie parente" type="submit" name="current" value=$parent_id|intval}
			</li>
		{/if}

		{foreach from=$categories item="c"}
			<li class="folder">{button shape="folder" label=$c.title type="submit" name="current" value=$c.id}</li>
		{foreachelse}
			<li class="help">Aucune sous-catégorie ici.</li>
		{/foreach}

		{if $id_page !== $current_cat_id}
			<li class="select">
				{button shape="right" label="Choisir la catégorie \"%s\""|args:$current_cat_title type="button" name="move" value=$current_cat_id|intval data-label=$current_cat_title}
			</li>
		{/if}
		</ul>
	</nav>

{literal}
<script type="text/javascript">
var buttons = document.querySelectorAll('button[name=move]');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.g.inputListSelected(e.value, e.dataset.label);
	};
});
</script>
{/literal}

{include file="_foot.tpl"}