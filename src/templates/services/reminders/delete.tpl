{include file="_head.tpl" title="Supprimer un rappel automatique" current="users/services"}

{include file="services/_nav.tpl" current="reminders"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce rappel automatique ?"
	warning="Êtes-vous sûr de vouloir supprimer le rappel « %s » ?"|args:$reminder.subject
	alert="Attention, cela supprimera également l'historique des emails envoyés par ce rappel."}

{include file="_foot.tpl"}