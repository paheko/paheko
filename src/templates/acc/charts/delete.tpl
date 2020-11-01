{include file="admin/_head.tpl" title="Supprimer un plan comptable" current="acc/charts"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce plan comptable ?"
	warning="Êtes-vous sûr de vouloir supprimer le plan comptable « %s » ?"|args:$chart.label
	csrf_key="acc_charts_delete_%s"|args:$chart.id
}

{include file="admin/_foot.tpl"}