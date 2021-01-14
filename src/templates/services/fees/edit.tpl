{include file="admin/_head.tpl" title="%s — Tarifs"|args:$service.label current="membres/services"}

{include file="services/_nav.tpl" current="index" current_service=$service service_page="index"}

{include file="services/fees/_fee_form.tpl" legend="Modifier un tarif" submit_label="Enregistrer"}

{include file="admin/_foot.tpl"}