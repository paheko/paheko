{include file="admin/_head.tpl" title="Bilan" current="acc/years"}

{include file="acc/reports/_header.tpl" current="balance_sheet" title="Bilan"}

{if $asset_sum != $liability_sum}
	<p class="alert block">
		<strong>Le bilan n'est pas équilibré&nbsp;!</strong><br />
		Vérifiez que vous n'avez pas oublié de reporter des soldes depuis le précédent exercice.
	</p>
{/if}

<table class="statement">
	<colgroup>
		<col width="50%" />
		<col width="50%" />
	</colgroup>
	<tbody>
		<tr>
			<td>
				{include file="acc/reports/_statement_table.tpl" accounts=$asset caption="Actif"}
			</td>
			<td>
				{include file="acc/reports/_statement_table.tpl" accounts=$liability caption="Passif"}
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
							<td class="money">{$asset_sum|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			</td>
			<td>
				<table>
					<tfoot>
						<tr>
							<th>Total passif</th>
							<td class="money">{$liability_sum|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			</td>
		</tr>
	</tfoot>
</table>

<p class="help">Toutes les écritures sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}