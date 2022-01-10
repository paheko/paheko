{include file="admin/_head.tpl" title="Inscrire à une activité" current="users/services"}

{include file="services/_nav.tpl" current="save" fee=null service=null}

{form_errors}

{include file="services/user/_service_user_form.tpl" create=true}

{include file="admin/_foot.tpl"}