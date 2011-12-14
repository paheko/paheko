{include file="admin/_head.tpl" title=$config.nom_asso current="home"}

<h3>Bienvenue, {$user.nom|escape} !</h3>

{if empty($user.date_cotisation)}
    <p class="error">Vous n'avez jamais réglé votre cotisation.</p>
{elseif $verif_cotisation === true}
    <p class="confirm">Cotisation réglée le {$user.date_cotisation|date_fr:'d/m/Y'} :-)</p>
{else}
    <p class="alert">Cotisation en retard ! (dernier règlement le {$user.date_cotisation|date_fr:'d/m/Y'})</p>
{/if}


{include file="admin/_foot.tpl"}