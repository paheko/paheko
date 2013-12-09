{include file="admin/_head.tpl" title="Compte de résultat" current="compta/exercices" body_id="rapport"}

<div class="exercice">
    <h2>{$config.nom_asso|escape}</h2>
    <p>Exercice comptable {if $exercice.cloture}clôturé{else}en cours{/if} du
        {$exercice.debut|date_fr:'d/m/Y'} au {$exercice.fin|date_fr:'d/m/Y'}, généré le {$cloture|date_fr:'d/m/Y'}</p>
</div>

<table>
    <colgroup>
        <col width="50%" />
        <col width="50%" />
    </colgroup>
    <tbody>
        <tr>
            <td>
                <table>
                    <caption><h3>Charges</h3></caption>
                    <tbody>
                    {foreach from=$compte_resultat.charges.comptes key="parent_code" item="parent"}
                        <tr class="parent">
                            <th>{$parent_code|get_nom_compte|escape}</th>
                            <td>{$parent.solde|html_money}</td>
                        </tr>
                        {foreach from=$parent.comptes item="solde" key="compte"}
                        <tr class="compte">
                            <th>{$compte|get_nom_compte|escape}</th>
                            <td>{$solde|html_money}</td>
                        </tr>
                        {/foreach}
                    {/foreach}
                    </tbody>
                </table>
            </td>
            <td>
                <table>
                    <caption><h3>Produits</h3></caption>
                    <tbody>
                    {foreach from=$compte_resultat.produits.comptes key="parent_code" item="parent"}
                        <tr class="parent">
                            <th>{$parent_code|get_nom_compte|escape}</th>
                            <td>{$parent.solde|html_money}</td>
                        </tr>
                        {foreach from=$parent.comptes item="solde" key="compte"}
                        <tr class="compte">
                            <th>{$compte|get_nom_compte|escape}</th>
                            <td>{$solde|html_money}</td>
                        </tr>
                        {/foreach}
                    {/foreach}
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td>
                <table>
                    <tfoot>
                        <tr>
                            <th>Total charges</th>
                            <td>{$compte_resultat.charges.total|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
           <td>
                <table>
                    <tfoot>
                        <tr>
                            <th>Total produits</th>
                            <td>{$compte_resultat.produits.total|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        <tr>
            <td>
            {if ($compte_resultat.resultat >= 0)}
                <table>
                    <tfoot>
                        <tr>
                            <th>Résultat (excédent)</th>
                            <td>{$compte_resultat.resultat|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            {/if}
            </td>
            <td>
            {if ($compte_resultat.resultat < 0)}
                <table>
                    <tfoot>
                        <tr>
                            <th>Résultat (déficit)</th>
                            <td>{$compte_resultat.resultat|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            {/if}
            </td>
        </tr>
    </tfoot>
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie|escape}.</p>

{include file="admin/_foot.tpl"}