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
							<td class="money" width="10%">{$statement.expense_sum|raw|money:false}</td>
							{if $statement.expense_sum2}
							<td class="money" width="10%">{$statement.expense_sum2|raw|money:false}</td>
							<td class="money" width="10%">{$statement.expense_change|raw|money:true:true}</td>
							{/if}
						</tr>
					</tfoot>
				</table>
			</td>
			<td>
				<table>
					<tfoot>
						<tr>
							<th>Total</th>
							<td class="money" width="10%">{$statement.revenue_sum|raw|money:false}</td>
							{if $statement.revenue_sum2}
							<td class="money" width="10%">{$statement.revenue_sum2|raw|money:false}</td>
							<td class="money" width="10%">{$statement.revenue_change|raw|money:true:true}</td>
							{/if}
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
							<td class="money" width="10%">{$statement.result|raw|money:false}</td>
							{if $statement.result2}
							<td class="money" width="10%">{$statement.result2|raw|money:false}</td>
							<td class="money" width="10%">{$statement.result_change|raw|money:true:true}</td>
							{/if}
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
							<td class="money" width="10%">{$statement.result|raw|money:false}</td>
							{if $statement.result2}
							<td class="money" width="10%">{$statement.result2|raw|money:false}</td>
							<td class="money" width="10%">{$statement.result_change|raw|money:true:true}</td>
							{/if}
						</tr>
					</tfoot>
				</table>
			{/if}
			</td>
		</tr>
		{/if}
	</tfoot>
</table>
