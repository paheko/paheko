<nav><ul class="breadcrumb"><li class="current">Destinataire</li></ul></nav>

<form method="POST" action="{{"./edit.html?show=quotation&id=%d&step=recipient&last_step=start"|args:$_GET.id}}">
	<fieldset>
		<legend><h2>Destinataire</h2></legend>
		{{if $document.recipient_business_name || $document.recipient_address}}
			{{:assign default_recipient_type="2"}}
		{{else}}
			{{:assign default_recipient_type="1"}}
		{{/if}}
		{{:input type="radio-btn" name="recipient_type" label="Membre enregistré·e" value="1" default=$default_recipient_type}}
		{{:input type="radio-btn" name="recipient_type" label="Saisie manuelle" value="2" default=$default_recipient_type}}
		{{if $document.recipient_member_id}}
			{{#users id=$document.recipient_member_id|intval}}{{:assign .='user'}}{{/users}}
			{{if $user}}
				{{:assign var='default_customer_selection.%s'|args:$document.recipient_member_id value=$user.nom}}
			{{/if}}
		{{/if}}
		<dd class="recipient_type_1">
			<dl>{{:input type="list" name="customer" required=false label="Membre destinataire" target="!users/selector.php" default=$default_customer_selection can_delete=true}}</dl>
			{{if $document.recipient_member_id && !$user}}
				<p class="infos">
					Attention : Ce devis est associé à l'ancien·ne membre
					{{if $document.recipient_member_number}}n°{{$document.recipient_member_number}}{{else}}#{{$document.recipient_member_id}}{{/if}}
					dont le compte est supprimé.<br />
					Mettre à jour ce document des-associera cet ancien compte.
				</p>
			{{/if}}
		</dd>
		<dl class="recipient_type_2">
			{{:input type="text" name="recipient_business_name" label="Raison sociale" source=$document placeholder="ex : SARL J'aime les deux vies"}}
			{{:input type="textarea" name="recipient_address" label="Adresse" source=$document placeholder=""}}
			{{:input type="list" name="customer" required=false label="Associé au membre" target="!users/selector.php" default=$default_customer_selection can_delete=true help="Si un·e membre est associé·e au document, la saisie manuelle est prioritaire sur son nom et adresse."}}
		</dl>
	</fieldset>

	<p class="submit">
		{{:button type="submit" name="quotation_submit" label="Continuer" class="main" shape="right"}}
	</p>
</form>

<script type="text/javascript">

var hide = [];
if (!$('#f_recipient_type_1').checked)
	hide.push('.recipient_type_1');

if (!$('#f_recipient_type_2').checked)
	hide.push('.recipient_type_2');

g.toggle(hide, false);

function togglePeriod()
{
	g.toggle(['.recipient_type_1', '.recipient_type_2'], false);

	if (this.checked && this.value == 1)
		g.toggle('.recipient_type_1', true);
	else if (this.checked && this.value == 2)
		g.toggle('.recipient_type_2', true);
}

$('#f_recipient_type_1').onchange = togglePeriod;
$('#f_recipient_type_2').onchange = togglePeriod;

</script>
