{include file="admin/_head.tpl" title="Supprimer une inscription" current="membres/services"}

{include file="services/_nav.tpl" current="index"}

{include file="common/delete_form.tpl"
	legend="Supprimer cette inscription ?"
	warning="Êtes-vous sûr de vouloir supprimer cette inscription ?"
	info="Les écritures comptables liées à cette inscription ne seront pas supprimées, la comptabilité demeurera inchangée."}

{include file="admin/_foot.tpl"}