{include file="admin/_head.tpl" title="Supprimer une activité" current="membres/services"}

{include file="services/_nav.tpl" current="index"}

{include file="common/delete_form.tpl"
	legend="Supprimer cette activité ?"
	warning="Êtes-vous sûr de vouloir supprimer l'activité « %s » ?"|args:$service.label
	alert="Attention, cela supprimera également l'historique des membres inscrits à cette activité, ainsi que les rappels associés."
	info="Les écritures comptables liées à l'historique des membres inscrits à cette activité ne seront pas supprimées, et la comptabilité demeurera inchangée."}

{include file="admin/_foot.tpl"}