{include file="_head.tpl" title="Supprimer un champ" current="config"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce champ ?"
	confirm="Cocher cette case pour supprimer le champ, cela effacera de manière permanente cette donnée de toutes les fiches membres."
	warning="Êtes-vous sûr de vouloir supprimer le champ « %s » ?"|args:$field.label
	alert="Attention, ce champ ainsi que les données qu'il contient seront supprimés de toutes les fiches membres existantes."
}

{include file="_foot.tpl"}