{include file="admin/_head.tpl" title="Compte de résultat" current="acc/years"}

{include file="acc/reports/_header.tpl" current="statement"}

<table class="statement">
	<colgroup>
		<col width="50%" />
		<col width="50%" />
	</colgroup>
	<tbody>
		<tr>
			<td>
				{include file="acc/reports/_statement_table.tpl" accounts=$expense caption="Charges" abs=true}
			</td>
			<td>
				{include file="acc/reports/_statement_table.tpl" accounts=$revenue caption="Produits" abs=true}
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
							<td class="money">{$expense_sum|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			</td>
			<td>
				<table>
					<tfoot>
						<tr>
							<th>Total produits</th>
							<td class="money">{$revenue_sum|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			</td>
		</tr>
		{if $result}
		<tr>
			<td>
			{if ($result < 0)}
				<table>
					<tfoot>
						<tr>
							<th>Résultat (déficit)</th>
							<td class="money">{$result|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			{/if}
			</td>
			<td>
			{if ($result >= 0)}
				<table>
					<tfoot>
						<tr>
							<th>Résultat (excédent)</th>
							<td class="money">{$result|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			{/if}
			</td>
		</tr>
		{/if}
	</tfoot>
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}