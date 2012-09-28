{include file="admin/_head.tpl" title="Compte de résultat" current="compta/gestion"}

<div id="compteResultat">
    <h2>En date du {$now|date_fr:'d/m/Y'}</h2>

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
                                <td>{$parent.solde|escape_money}</td>
                            </tr>
                            {foreach from=$parent.comptes item="solde" key="compte"}
                            <tr class="compte">
                                <th>{$compte|get_nom_compte|escape}</th>
                                <td>{$solde|escape_money}</td>
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
                                <td>{$parent.solde|escape_money}</td>
                            </tr>
                            {foreach from=$parent.omptes item="solde" key="compte"}
                            <tr>
                                <td>{$compte|get_nom_compte|escape}</td>
                                <td>{$solde|escape_money}</td>
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
                                <td>{$compte_resultat.charges.total|escape_money}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
               <td>
                    <table>
                        <tfoot>
                            <tr>
                                <th>Total produits</th>
                                <td>{$compte_resultat.produits.total|escape_money}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td>
                    <table>
                        <tfoot>
                            <tr>
                                <th>Résultat</th>
                                <td>{$compte_resultat.resultat|escape_money}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

{include file="admin/_foot.tpl"}