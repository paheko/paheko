{include file="_head.tpl" title="Supprimer un rappel automatique" current="users/services"}

{include file="services/_nav.tpl" current="reminders"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce rappel automatique ?"
	warning="Êtes-vous sûr de vouloir supprimer le rappel « %s » ?"|args:$reminder.subject
	confirm="Cocher cette case pour supprimer aussi l'historique des messages envoyés."}

{include file="_foot.tpl"}