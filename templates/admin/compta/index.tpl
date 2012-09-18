{include file="admin/_head.tpl" title="Comptabilité" current="compta"}

<p class="alert">
    Attention la comptabilité est une fonctionnalité en beta,
    il est déconseillé pour le moment de l'utiliser pour la
    comptabilité réelle de votre association.
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