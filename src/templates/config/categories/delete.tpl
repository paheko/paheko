{include file="_head.tpl" title="Supprimer une catégorie de membre" current="config"}

{include file="config/_menu.tpl" current="categories"}

{include file="common/delete_form.tpl"
	legend="Supprimer cette catégorie de membres ?"
	warning="Êtes-vous sûr de vouloir supprimer la catégorie « %s » ?"|args:$cat.name
	alert="Attention, la catégorie ne doit plus contenir de membres pour pouvoir être supprimée."
	info="Les écritures comptables liées à l'historique des membres inscrits à cette activité ne seront pas supprimées, et la comptabilité demeurera inchangée."}

{include file="_foot.tpl"}