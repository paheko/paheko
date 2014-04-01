{include file="admin/_head.tpl" title="Supprimer un rappel automatique" current="membres/cotisations"}

<ul class="actions">
    <li><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    <li class="current"><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
</ul>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Supprimer ce rappel automatique ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer le rappel «&nbsp;{$rappel.sujet|escape}&nbsp;» ?
        </h3>
        <dl>
            <dt><label for="f_delete_history">Effacer aussi l'historique des e-mails envoyés par le biais de ce rappel&nbsp;?</label></dt>
            <dd>
                <label>
                    <input type="radio" name="delete_history" value="0" checked="checked" />
                    Non, conserver l'historique
                </label> (toutefois il ne sera plus associé à ce rappel)
            </dd>
            <dd>
                <label>
                    <input type="radio" name="delete_history" value="1" />
                    Oui, effacer l'historique des e-mails envoyés
                </label>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_rappel_"|cat:$rappel.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}