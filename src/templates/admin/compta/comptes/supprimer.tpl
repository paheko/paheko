{include file="admin/_head.tpl" title="Supprimer un compte" current="compta/categories"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

{if !$can_delete && !$can_disable}
    <p class="alert">
        Ce compte ne peut être supprimé ou désactivé.
        Pour pouvoir supprimer ou désactiver un compte aucune catégorie ou écriture comptable ne doit y faire référence.
        Pour pouvoir désactiver un compte aucune écriture comptable ne doit y faire référence dans l'exercice en cours.
    </p>
{elseif $can_disable && !$can_delete}

    <form method="post" action="{$self_url|escape}">

        <fieldset>
            <legend>Désactiver le compte ?</legend>
            <h3 class="warning">
                Êtes-vous sûr de vouloir désactiver le compte «&nbsp;{$compte.id|escape} - {$compte.libelle|escape}&nbsp;»&nbsp;?
            </h3>
            <p class="help">
                Une fois désactivé il ne sera plus possible de l'utiliser, mais il pourra par contre être réactivé.
            </p>
        </fieldset>

        <p class="submit">
            {csrf_field key="compta_disable_compte_`$compte.id`"}
            <input type="submit" name="disable" value="Désactiver &rarr;" />
        </p>

    </form>
{else}
    <form method="post" action="{$self_url|escape}">

        <fieldset>
            <legend>Supprimer le compte ?</legend>
            <h3 class="warning">
                Êtes-vous sûr de vouloir supprimer le compte «&nbsp;{$compte.id|escape} - {$compte.libelle|escape}&nbsp;»&nbsp;?
            </h3>
        </fieldset>

        <p class="submit">
            {csrf_field key="compta_delete_compte_`$compte.id`"}
            <input type="submit" name="delete" value="Supprimer &rarr;" />
        </p>

    </form>
{/if}

{include file="admin/_foot.tpl"}