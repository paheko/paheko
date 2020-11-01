{include file="admin/_head.tpl" title="Supprimer l'écriture n°%d"|args:$transaction.id current="acc"}

{include file="common/delete_form.tpl"
	legend="Supprimer cette écriture ?"
	warning="Êtes-vous sûr de vouloir supprimer l'écriture n°%d « %s » ?"|args:$transaction.id,$transaction.label
	csrf_key="acc_delete_%s"|args:$transaction.id
}

{include file="admin/_foot.tpl"}