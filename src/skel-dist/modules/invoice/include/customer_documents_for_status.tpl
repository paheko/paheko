{{:assign var="label" from="STATUS_LABELS.%s"|args:$status}}
<h3 class="ruler">{{$label}}</h3>
{{#list
	select=$select
	where=$where
	order=$order
	:business_name=$_GET.id
	:type=$type
	:status=$status
	module=$module.name
}}
<tr>
	<td><a href="details.html?id={{$id|intval}}">{{$key}}</a></td>
	<td>{{$date|date_short}}</td>
	<td>{{$deadline|date_short}}</td>
	<td><a href="details.html?id={{$id|intval}}">{{$subject}}</a></td>
	<td class="money">{{$total|money_currency}}</td>
	<td class="num">
		{{if $recipient_member_id}}
			{{:link href="!users/details.php?id=%s"|args:$recipient_member_numero label=$recipient_member_numero}}
		{{else}}
		-
		{{/if}}
	</td>
	<td class="actions">
		{{:include file='./document_list_buttons.html'}}
		{{*
		{{#restrict section="accounting" level="write"}}
			{{if !$archived && $status == $DRAFT_STATUS}}
			{{:linkbutton shape="edit" label="Modifier" href="edit.html?id=%d"|args:$id}}
			{{/if}}
		{{/restrict}}
		{{if $status !== $DRAFT_STATUS}}
			{{:linkbutton label="PDF" href="preview.html?id=%s"|args:$id shape="document"}}
		{{else}}
			{{:linkbutton label="Aperçu" href="preview.html?id=%s"|args:$id shape="document"}}
		{{/if}}
		*}}
	</td>
</tr>

{{else}}
	<p>Aucun document pour le status "{{$label}}".</p>
{{/list}}
