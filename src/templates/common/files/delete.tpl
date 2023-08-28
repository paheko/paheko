{include file="_head.tpl" title="Supprimer un fichier" current=null}

{if $trash}
	{if $file.type == $file::TYPE_DIRECTORY}
		{include file="common/delete_form.tpl"
			shape="trash"
			legend="Supprimer ce dossier ?"
			warning="Êtes-vous sûr de vouloir mettre le dossier « %s » à la corbeille ?"|args:$file.name
			alert="Tous les sous-dossiers et fichiers de ce dossier seront placés à la corbeille !"
			info="Seul un membre administrateur pourra récupérer le fichier dans la corbeille."
		}
	{else}
		{include file="common/delete_form.tpl"
			shape="trash"
			legend="Supprimer ce fichier ?"
			warning="Êtes-vous sûr de vouloir mettre le fichier « %s » à la corbeille ?"|args:$file.name
			info="Seul un membre administrateur pourra récupérer le fichier dans la corbeille."
		}
	{/if}
{else}
	{if $file.type == $file::TYPE_DIRECTORY}
		{include file="common/delete_form.tpl"
			legend="Supprimer ce dossier ?"
			warning="Êtes-vous sûr de vouloir supprimer le dossier « %s » ?"|args:$file.name
			alert="Tous les sous-dossiers et fichiers de ce dossier seront supprimés !"
			info="Il ne sera pas possible de récupérer les données."
		}
	{else}
		{include file="common/delete_form.tpl"
			legend="Supprimer ce fichier ?"
			warning="Êtes-vous sûr de vouloir supprimer le fichier « %s » ?"|args:$file.name
			info="Il ne sera pas possible de récupérer les données."
		}
	{/if}
{/if}

{include file="_foot.tpl"}