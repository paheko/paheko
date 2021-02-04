{include file="admin/_head.tpl" title="Supprimer un fichier" current=null is_popup=1 body_id="popup"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce fichier ?"
	warning="Êtes-vous sûr de vouloir supprimer le fichier « %s » ?"|args:$file.name
}

{include file="admin/_foot.tpl"}