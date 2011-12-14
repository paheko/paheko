{include file="admin/_head.tpl" title="Contacter un membre" current="membres"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">
    <fieldset>
        <legend>Message</legend>
        <dl>
            <dt>Destinataire</dt>
            <dd>{$membre.nom|escape} ({$categorie.nom|escape})</dd>
            <dt>Sujet</dt>
            <dd></dd>
            <dt>Message</dt>
            <dd>
                <p class="alert">
                    FIXME: pas encore développé
                </p>
            </dd>
        </dl>
    </fieldset>
</form>

{include file="admin/_foot.tpl"}