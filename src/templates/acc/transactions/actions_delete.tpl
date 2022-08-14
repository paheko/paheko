{include file="_head.tpl" title="Supprimer %d écritures"|args:$count current="acc"}

{include file="common/delete_form.tpl"
	legend="Supprimer ces écritures ?"
	warning="Êtes-vous sûr de vouloir supprimer %d écritures ?"|args:$count
	confirm="Cocher cette case pour confirmer la suppression"
	csrf_key=$csrf_key
	extra=$extra
}

{include file="_foot.tpl"}