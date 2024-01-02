{include file="_head.tpl" title="Supprimer une activité" current="users/services"}

{include file="services/_nav.tpl" current="index"}

{include file="common/delete_form.tpl"
	legend="Supprimer cette activité ?"
	confirm_label=$confirm_label
	confirm_text=$confirm_text
	warning="Êtes-vous sûr de vouloir supprimer l'activité « %s » et toutes les inscriptions ?"|args:$service.label
	error="Attention, cela supprimera également les tarifs, les inscriptions des membres à cette activité, ainsi que les rappels associés !"
	info="Les écritures comptables liées à l'historique des membres inscrits à cette activité ne seront pas supprimées, et la comptabilité demeurera inchangée."}

{include file="_foot.tpl"}