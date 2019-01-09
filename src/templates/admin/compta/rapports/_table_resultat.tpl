<table>
    <colgroup>
        <col width="50%" />
        <col width="50%" />
    </colgroup>
    <tbody>
        <tr>
            <td>
                <table>
                    {if $header}
                    <caption><h3>Charges</h3></caption>
                    {/if}
                    <tbody>
                    {foreach from=$comptes.charges.comptes key="parent_code" item="parent"}
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
                    {if $header}
                    <caption><h3>Produits</h3></caption>
                    {/if}
                    <tbody>
                    {foreach from=$comptes.produits.comptes key="parent_code" item="parent"}
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
                            <td>{$comptes.charges.total|escape|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
           <td>
                <table>
                    <tfoot>
                        <tr>
                            <th>Total produits</th>
                            <td>{$comptes.produits.total|escape|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        {if $result}
        <tr>
            <td>
            {if ($comptes.resultat >= 0)}
                <table>
                    <tfoot>
                        <tr>
                            <th>Résultat (excédent)</th>
                            <td>{$comptes.resultat|escape|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            {/if}
            </td>
            <td>
            {if ($comptes.resultat < 0)}
                <table>
                    <tfoot>
                        <tr>
                            <th>Résultat (déficit)</th>
                            <td>{$comptes.resultat|escape|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            {/if}
            </td>
        </tr>
        {/if}
    </tfoot>
</table>