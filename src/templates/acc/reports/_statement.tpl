<table class="statement">
	<colgroup>
		<col width="50%" />
		<col width="50%" />
	</colgroup>
	<tbody>
		<tr>
			<td>
				{include file="acc/reports/_statement_table.tpl" accounts=$statement.body_left caption=$statement.caption_left}
			</td>
			<td>
				{include file="acc/reports/_statement_table.tpl" accounts=$statement.body_right caption=$statement.caption_right}
			</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td>
				<table>
					<tfoot>
						{foreach from=$statement.foot_left item="row"}
						<tr>
							<th>{$row.label}</th>
							{if $row.balance2}
							<td class="money" width="10%">{$row.balance2|raw|money:false}</td>
							{/if}

							<td class="money" width="10%">{$row.balance|raw|money:false}</td>

							{if $row.change}
							<td class="money" width="10%">{$row.change|raw|money:false:true}</td>
							{/if}
						</tr>
						{/foreach}
					</tfoot>
				</table>
			</td>
			<td>
				<table>
					<tfoot>
						{foreach from=$statement.foot_right item="row"}
						<tr>
							<th>{$row.label}</th>
							{if $row.balance2}
							<td class="money" width="10%">{$row.balance2|raw|money:false}</td>
							{/if}

							<td class="money" width="10%">{$row.balance|raw|money:false}</td>

							{if $row.change}
							<td class="money" width="10%">{$row.change|raw|money:false:true}</td>
							{/if}
						</tr>
						{/foreach}
					</tfoot>
				</table>
			</td>
		</tr>
	</tfoot>
</table>
