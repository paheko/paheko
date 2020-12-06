{include file="admin/_head.tpl" title="Supprimer un tarif" current="membres/services"}

{include file="services/_nav.tpl" current="index"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce tarif ?"
	warning="Êtes-vous sûr de vouloir supprimer le tarif « %s » ?"|args:$fee.label
	alert="Attention, cela supprimera également l'historique des membres ayant réglé ce tarif."
	info="Les écritures comptables liées à l'historique des membres ayant réglé ce tarif ne seront pas supprimées, et la comptabilité demeurera inchangée."}

{include file="admin/_foot.tpl"}