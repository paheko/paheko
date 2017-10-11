{include file="admin/_head.tpl" title="Bilan" current="compta/exercices" body_id="rapport"}

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
                    <caption><h3>Actif</h3></caption>
                    <tbody>
                    {foreach from=$bilan.actif.comptes key="parent_code" item="parent"}
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
                    <caption><h3>Passif</h3></caption>
                    <tbody>
                    {foreach from=$bilan.passif.comptes key="parent_code" item="parent"}
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
                            <th>Total actif</th>
                            <td>{$bilan.actif.total|escape|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
           <td>
                <table>
                    <tfoot>
                        <tr>
                            <th>Total passif</th>
                            <td>{$bilan.passif.total|escape|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
    </tfoot>
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}