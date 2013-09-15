{include file="admin/_head.tpl" title="Bilan" current="compta/exercices" body_id="rapport"}

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
                    <caption><h3>Actif</h3></caption>
                    <tbody>
                    {foreach from=$bilan.actif.comptes key="parent_code" item="parent"}
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
                    <caption><h3>Passif</h3></caption>
                    <tbody>
                    {foreach from=$bilan.passif.comptes key="parent_code" item="parent"}
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
                            <th>Total actif</th>
                            <td>{$bilan.actif.total|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
           <td>
                <table>
                    <tfoot>
                        <tr>
                            <th>Total passif</th>
                            <td>{$bilan.passif.total|html_money}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
    </tfoot>
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie|escape}.</p>

{include file="admin/_foot.tpl"}