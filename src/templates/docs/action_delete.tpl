{include file="_head.tpl" title="Supprimer %d fichiers"|args:$count current="docs"}

{include file="common/delete_form.tpl"
	legend="Supprimer ces fichiers ?"
	warning="Êtes-vous sûr de vouloir mettre %d fichiers à la corbeille ?"|args:$count
	csrf_key=$csrf_key
	extra=$extra
}

{include file="_foot.tpl"}