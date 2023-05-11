{include file="_head.tpl" title="Supprimer un membre" current="users"}

{include file="common/delete_form.tpl"
	legend="Supprimer ce membre ?"
	warning=$warning
	alert="Cette action est irréversible et effacera toutes les données et l'historique de ce membre."
	info="Alternativement, il est aussi possible de déplacer le membre dans une catégorie « Anciens membres », plutôt que de le supprimer complètement."
	csrf_key=$csrf_key
}

{include file="_foot.tpl"}