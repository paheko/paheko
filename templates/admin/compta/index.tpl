{include file="admin/_head.tpl" title="Comptabilité" current="compta"}

<p class="alert">
    <strong>Attention !</strong>
    La comptabilité est une fonctionnalité en beta,
    il est déconseillé pour le moment de l'utiliser pour la
    comptabilité réelle de votre association.<br />
    Vous êtes cependant encouragé à la tester et à faire part
    de votre retour sur le site de <a href="http://dev.kd2.org/garradin/">Garradin</a>.
</p>

<h3>Évolution des recettes et dépenses sur les 30 derniers jours</h3>
<p>
    <img src="{$www_url}admin/compta/graph.php?g=recettes_depenses" />
</p>

<h3>Évolution actif/passif sur les 30 derniers jours</h3>
<p>
    <img src="{$www_url}admin/compta/graph.php?g=actif_passif" />
</p>

{include file="admin/_foot.tpl"}