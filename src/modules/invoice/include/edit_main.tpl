{{* Default values (only) for new documents *}}
{{if !$document}}
	{{:assign org_contact_default=$config.org_email}}
	{{:assign introduction_text_helper='La valeur pré-remplie de ce texte est modifiable dans la configuration du module.'}}
	{{:assign introduction_text_default=$module.config.introduction_text}}
{{else}}
	{{:assign org_contact_default=null}}
	{{:assign introduction_text_helper=null}}
	{{:assign introduction_text_default=null}}
{{/if}}

<nav><ul class="breadcrumb"><li>Destinataire</li><li class="current">Objet</li></ul></nav>

<form method="POST" action="{{"./edit.html?show=quotation&id=%d&step=main&last_step=recipient"|args:$_GET.id}}">

	<fieldset>
		<legend><h2>Objet</h2></legend>
		<dl>
			{{:input type="text" name="key" label="Numéro" source=$document help="Ce numéro doit être unique. Laisser vide pour générer un nouveau numéro."}}
			{{:input type="text" name="subject" label="Intitulé" source=$document placeholder="ex : Devis pour une location de barnum" required=true}}
			{{:input type="date" name="date" label="Date du devis" source=$document required=true}}
			{{:input type="date" name="deadline" label="Échéance pour validation" source=$document required=false}}
			{{:input type="text" name="org_contact" label="Contact" source=$document default=$org_contact_default required=true}}
			{{:input type="textarea" name="introduction_text" label="Texte d'introduction" source=$document default=$introduction_text_default placeholder="ex : Formule de politesse." help=$introduction_text_helper required=false cols="50" rows="4"}}
		</dl>
	</fieldset>

	{{:assign var='customer_name' from='_POST.customer.%d'|args:$customer_id}}
	{{:input type="hidden" name="customer[%d]"|args:$customer_id default=$customer_name}}
	{{:input type="hidden" name="recipient_business_name" default=$_POST.recipient_business_name}}
	{{:input type="hidden" name="recipient_address" default=$_POST.recipient_address}}

	<p class="submit">
		{{:button type="submit" name="quotation_submit" label="Continuer" class="main" shape="right"}}
	</p>

</form>
