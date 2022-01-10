{include file="admin/_head.tpl" title="Supprimer un membre" current="users"}

{include file="common/delete_form.tpl"
    legend="Supprimer ce membre ?"
    warning="Êtes-vous sûr de vouloir supprimer le membre « %s » ?"|args:$user->name()
    alert="Cette action est irréversible et effacera toutes les données personnelles et l'historique de ces membres."
    info="Alternativement, il est aussi possible de déplacer les membres qui ne font plus partie de l'association dans une catégorie « Anciens membres », plutôt que de les effacer complètement."
    csrf_key=$csrf_key
}

{include file="admin/_foot.tpl"}