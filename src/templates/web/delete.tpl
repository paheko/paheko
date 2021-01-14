{include file="admin/_head.tpl" title=$title current="web"}

{include file="common/delete_form.tpl"
	legend=$title
	warning="Êtes-vous sûr de vouloir supprimer « %s » ?"|args:$page.title
	alert=$alert
}

{include file="admin/_foot.tpl"}