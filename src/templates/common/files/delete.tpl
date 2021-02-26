{include file="admin/_head.tpl" title="Supprimer un fichier" current=null}

{if $file.type == $file::TYPE_DIRECTORY}
	{include file="common/delete_form.tpl"
		legend="Supprimer ce répertoire ?"
		warning="Êtes-vous sûr de vouloir supprimer le répertoire « %s » ?"|args:$file.name
		alert="Tous les sous-répertoires et fichiers de ce répertoire seront supprimés !"
	}
{else}
	{include file="common/delete_form.tpl"
		legend="Supprimer ce fichier ?"
		warning="Êtes-vous sûr de vouloir supprimer le fichier « %s » ?"|args:$file.name
	}
{/if}

{include file="admin/_foot.tpl"}