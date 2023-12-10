<?php
assert(isset($columns));
assert(isset($s));
assert(isset($is_admin));
$is_unprotected = $s->type == $s::TYPE_SQL_UNPROTECTED;
$sql_disabled = (!$session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN) && $is_unprotected);
?>

{form_errors}

<fieldset>
{if $s.type !== $s::TYPE_JSON}
	{if $sql_disabled}
		<legend>Recherche enregistrée</legend>
		<h3>{$s.label}</h3>
	{else}
		<legend>Recherche SQL</legend>
		<dl>
			{input type="textarea" name="sql" cols="100" rows="8" required=1 label="Requête SQL" help="Si aucune limite n'est précisée, une limite de 100 résultats sera appliquée." default=$s.content}
			{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
				{input type="checkbox" name="unprotected" value=1 label="Autoriser l'accès à toutes les tables de la base de données" default=$is_unprotected}
				<dd class="help">Attention : en cochant cette case vous autorisez la requête à lire toutes les données de toutes les tables de la base de données&nbsp;!</dd>
			{/if}

			<dd>
				{foreach from=$schema item="table"}
				<details>
					<summary>Table&nbsp;: <strong>{$table.comment}</strong> (<tt>{$table.name}</tt>)</summary>
					{include file="common/_sql_table.tpl" indexes=null class=null}
					</div>
				</details>
				{/foreach}
			</dd>
		</dl>
		<p class="submit">
			{button type="submit" name="run" label="Exécuter" shape="search" class="main"}
			<input type="hidden" name="id" value="{$s.id}" />
			{if $s->exists()}
				{button name="save" value=1 type="submit" label="Enregistrer : %s"|args:$s.label|truncate:40:"…":true shape="upload"}
				{button name="save_new" value=1 type="submit" label="Enregistrer nouvelle recherche" shape="plus"}
			{else}
				{button name="save" value=1 type="submit" label="Enregistrer cette recherche" shape="upload"}
			{/if}
			{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
				{linkbutton href="!config/advanced/sql.php" target="_blank" shape="menu" label="Voir le schéma SQL complet"}
			{/if}
		</p>
	{/if}
{else}
	<legend>{if isset($legend)}{$legend}{else}Rechercher{/if}</legend>

	<div class="queryBuilder" id="queryBuilder" data-groups="{$s->getGroups()|escape:'json'|escape}" data-columns="{$columns|escape:'json'|escape}"></div>
	<p class="submit">
		{button name="search" value=1 type="submit" label="Chercher" shape="search" id="send" class="main"}
		<input type="hidden" name="q" id="jsonQuery" />
		<input type="hidden" name="id" value="{$s.id}" />
		{if $s.id}
			{button name="save" value=1 type="submit" label="Enregistrer : %s"|args:$s.label|truncate:40:"…":true shape="upload"}
			{button name="save_new" value=1 type="submit" label="Enregistrer nouvelle recherche" shape="plus"}
		{else}
			{button name="save" value=1 type="submit" label="Enregistrer cette recherche" shape="upload"}
		{/if}
		{if $is_admin}
			{button name="to_sql" value=1 type="submit" label="Recherche SQL" shape="edit"}
		{/if}
	</p>
	<script type="text/javascript" src="{$admin_url}static/scripts/advanced_search.js"></script>
{/if}
</fieldset>
