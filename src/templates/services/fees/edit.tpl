{include file="admin/_head.tpl" title="%s — Tarifs"|args:$service.label current="membres/services" js=1}

{include file="services/_nav.tpl" current="index"}

{include file="services/fees/_fee_form.tpl" legend="Modifier un tarif" submit_label="Enregistrer"}

{include file="admin/_foot.tpl"}