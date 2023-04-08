{include file="_head.tpl" title="Inscrire à une activité" current="users/services"}

{if !$dialog}
{include file="services/_nav.tpl" current="save" fee=null service=null}
{/if}

{form_errors}

{include file="services/user/_service_user_form.tpl" create=true}

{include file="_foot.tpl"}