{include file="admin/_head.tpl" title="Compte de résultat" current="compta/exercices" body_id="rapport"}

{include file="admin/compta/rapports/_header.tpl"}

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
                            <th>{$parent_code|get_nom_compte}</th>
                            <td>{$parent.solde|escape|html_money}</td>
                        </tr>
                        {foreach from=$parent.comptes item="solde" key="compte"}
                        <tr class="compte">
                            <th>{$compte|get_nom_compte}</th>
                            <td>{$solde|escape|html_money}</td>
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
                            <th>{$parent_code|get_nom_compte}</th>
                            <td>{$parent.solde|escape|html_money}</td>
                        </tr>
                        {foreach from=$parent.comptes item="solde" key="compte"}
                        <tr class="compte">
                            <th>{$compte|get_nom_compte}</th>
                            <td>{$solde|escape|html_money}</td>
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
                            <td>{$compte_resultat.charges.total|escape|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
           <td>
                <table>
                    <tfoot>
                        <tr>
                            <th>Total produits</th>
                            <td>{$compte_resultat.produits.total|escape|html_money}</td>
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
                            <td>{$compte_resultat.resultat|escape|html_money}</td>
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
                            <td>{$compte_resultat.resultat|escape|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            {/if}
            </td>
        </tr>
    </tfoot>
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}