{include file="_head.tpl" title="Désinstaller une extension" current="config"}

{if $plugin}
	{include file="common/delete_form.tpl"
		legend="Supprimer une extension"
		confirm="Cocher cette case pour confirmer la suppression de toutes les données liées à cette extension"
		warning="Êtes-vous sûr de vouloir supprimer l'extension « %s » ?"|args:$plugin.label
		alert="Attention, cela supprimera toutes les données liées à l'extension !"}
{elseif $mode == 'data'}
	{include file="common/delete_form.tpl"
		legend="Supprimer les données d'une extension"
		confirm="Cocher cette case pour confirmer la suppression de toutes les données liées à cette extension"
		warning="Êtes-vous sûr de vouloir supprimer les données de l'extension « %s » ?"|args:$module.label
		alert="Attention, cela supprimera toutes les données liées à l'extension"}
{else}
	{include file="common/delete_form.tpl"
		legend="Supprimer une extension"
		confirm="Cocher cette case pour confirmer la suppression de cette extension"
		warning="Êtes-vous sûr de vouloir supprimer l'extension « %s » ?"|args:$module.label
		alert="Attention, cela supprimera toutes les données liées à l'extension, ainsi que l'extension elle-même."}
{/if}

{include file="_foot.tpl"}