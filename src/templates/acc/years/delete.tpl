{include file="admin/_head.tpl" title="Supprimer un exercice" current="acc/years"}

{include file="common/delete_form.tpl"
	legend="Supprimer cet exercice ?"
	warning="Êtes-vous sûr de vouloir supprimer l'exercice « %s » ?"|args:$year.label
	alert="Attention, l'exercice ne pourra pas être supprimé si des écritures y sont toujours affectées."
	csrf_key="acc_years_delete_%s"|args:$year.id
}

{include file="admin/_foot.tpl"}