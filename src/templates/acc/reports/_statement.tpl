<table class="statement">
	<colgroup>
		<col width="50%" />
		<col width="50%" />
	</colgroup>
	<tbody>
		<tr>
			<td>
				{include file="acc/reports/_statement_table.tpl" accounts=$statement.expense caption=$caption1}
			</td>
			<td>
				{include file="acc/reports/_statement_table.tpl" accounts=$statement.revenue caption=$caption2}
			</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td>
				<table>
					<tfoot>
						<tr>
							<th>Total</th>
							<td class="money">{$statement.expense_sum|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			</td>
			<td>
				<table>
					<tfoot>
						<tr>
							<th>Total</th>
							<td class="money">{$statement.revenue_sum|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			</td>
		</tr>
		{if $statement.result}
		<tr>
			<td>
			{if ($statement.result < 0)}
				<table>
					<tfoot>
						<tr>
							<th>Résultat (perte)</th>
							<td class="money">{$statement.result|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			{/if}
			</td>
			<td>
			{if ($statement.result >= 0)}
				<table>
					<tfoot>
						<tr>
							<th>Résultat (excédent)</th>
							<td class="money">{$statement.result|raw|html_money:false}</td>
						</tr>
					</tfoot>
				</table>
			{/if}
			</td>
		</tr>
		{/if}
	</tfoot>
</table>
