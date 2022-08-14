{include file="_head.tpl" title="Supprimer un exercice" current="acc/years"}

{include file="common/delete_form.tpl"
	legend="Supprimer cet exercice ?"
	warning="Êtes-vous sûr de vouloir supprimer l'exercice « %s » et ses %d écritures ?"|args:$year.label,$nb_transactions
	alert="Attention, il ne sera pas possible de récupérer les écritures supprimées."
	confirm="Cocher cette case pour confirmer la suppression de cet exercice et des %d écritures liées."|args:$nb_transactions
	csrf_key="acc_years_delete_%s"|args:$year.id
}

{include file="_foot.tpl"}