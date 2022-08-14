{include file="_head.tpl" title="Supprimer une activité" current="users/services"}

{include file="services/_nav.tpl" current="index"}

{include file="common/delete_form.tpl"
	legend="Supprimer cette activité ?"
	confirm="Cocher cette case pour supprimer l'activité, les tarifs associés, toutes les inscriptions et les rappels !"
	warning="Êtes-vous sûr de vouloir supprimer l'activité « %s » ?"|args:$service.label
	alert="Attention, cela supprimera également tous les tarifs, mais aussi l'historique des membres inscrits à cette activité, ainsi que les rappels associés."
	info="Les écritures comptables liées à l'historique des membres inscrits à cette activité ne seront pas supprimées, et la comptabilité demeurera inchangée."}

{include file="_foot.tpl"}