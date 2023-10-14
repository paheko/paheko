{include file="_head.tpl" title="Supprimer un envoi de message collectif" current="users/mailing"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce message collectif ?"
	warning="Êtes-vous sûr de vouloir supprimer le message « %s » ?"|args:$mailing.subject
	info="La liste des destinataires sera également supprimée."}

{include file="_foot.tpl"}