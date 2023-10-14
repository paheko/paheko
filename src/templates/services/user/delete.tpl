{include file="_head.tpl" title="%s : Supprimer une inscription"|args:$user_name current="users/services"}

{include file="common/delete_form.tpl"
	legend="Supprimer l'inscription ?"
	warning="Êtes-vous sûr de vouloir supprimer l'inscription ?"
	alert="Les écritures comptables liées à cette inscription ne seront pas supprimées, la comptabilité demeurera inchangée."
	info="%s – à « %s — %s »"|args:$user_name,$service_name,$fee_name}

{include file="_foot.tpl"}