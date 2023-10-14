{include file="_head.tpl" title="Supprimer l'écriture n°%d"|args:$transaction.id current="acc"}

{include file="common/delete_form.tpl"
	legend="Supprimer cette écriture ?"
	warning="Êtes-vous sûr de vouloir supprimer l'écriture n°%d « %s » ?"|args:$transaction.id,$transaction.label
	csrf_key=$csrf_key
}

{include file="_foot.tpl"}