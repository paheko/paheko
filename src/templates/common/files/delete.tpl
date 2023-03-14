{include file="_head.tpl" title="Supprimer un fichier" current=null}

{if $file.type == $file::TYPE_DIRECTORY}
	{include file="common/delete_form.tpl"
		legend="Supprimer ce répertoire ?"
		warning="Êtes-vous sûr de vouloir mettre le répertoire « %s » à la corbeille ?"|args:$file.name
		alert="Tous les sous-répertoires et fichiers de ce répertoire seront placés à la corbeille !"
		info="Seul un membre administrateur pourra récupérer le fichier dans la corbeille."
	}
{else}
	{include file="common/delete_form.tpl"
		legend="Supprimer ce fichier ?"
		warning="Êtes-vous sûr de vouloir mettre le fichier « %s » à la corbeille ?"|args:$file.name
		info="Seul un membre administrateur pourra récupérer le fichier dans la corbeille."
	}
{/if}

{include file="_foot.tpl"}