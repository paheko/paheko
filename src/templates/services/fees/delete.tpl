{include file="_head.tpl" title="Supprimer un tarif" current="users/services"}

{include file="services/_nav.tpl" current="index"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce tarif ?"
	confirm_label=$confirm_label
	confirm_text=$confirm_text
	warning="Êtes-vous sûr de vouloir supprimer le tarif « %s » ?"|args:$fee.label
	error="Attention, cela supprimera également les inscriptions des membres à ce tarif !"
	info="Les écritures comptables liées à l'historique des membres ayant réglé ce tarif ne seront pas supprimées, et la comptabilité demeurera inchangée."}

{include file="_foot.tpl"}