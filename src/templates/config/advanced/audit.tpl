{include file="_head.tpl" title="Journal d'audit — Historique des actions des membres" current="config"}

{include file="../_menu.tpl" current="advanced" sub_current="audit"}

<p class="help">
	Cette page liste les tentatives de connexion, les modifications de mot de passe ou d'identifiant, et toutes les actions de création, suppression ou modification effectuées par tous les membres.
</p>

{if $list->count()}
	{include file="users/_log_list.tpl"}
{else}
	<p class="block alert">
		Aucune activité trouvée.
	</p>
{/if}

</form>

{include file="_foot.tpl"}