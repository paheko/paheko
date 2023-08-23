{if $params.history}
	{include file="_head.tpl" title="Historique des modifications de la fiche membre"}
{else}
	{include file="_head.tpl" title="Journal d'audit du membre"}
{/if}

{if $params.id_user}
	{include file="users/_nav_user.tpl" id=$params.id_user}
{elseif $params.history}
	{include file="users/_nav_user.tpl" id=$params.history}
{else}
	{include file="me/_nav.tpl" current="security"}
{/if}

{if !$params.history}
<p class="help">
	Cette page liste les tentatives de connexion, les modifications de mot de passe ou d'identifiant, et toutes les actions de création, suppression ou modification effectuées par ce membre.
</p>
{/if}

{if $list->count()}
	{include file="users/_log_list.tpl"}
{else}
	<p class="block alert">
		Aucune activité trouvée.
	</p>
{/if}

</form>

{include file="_foot.tpl"}