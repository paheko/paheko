{include file="_head.tpl" title="Nouveau message collectif" current="users/mailing"}

{form_errors}

<form method="post" action="" data-focus=1>
{if !$target_type}
	<fieldset>
		<legend>Sujet du message</legend>
		<dl>
			{input type="text" required="true" label="Sujet du message" name="subject" class="full-width"}
		</dl>
	</fieldset>
	<fieldset>
		<legend>Qui doit recevoir ce message&nbsp;?</legend>
		<dl>
			{input type="radio-btn" name="target_type" value="field" label="Membres correspondant à une case à cocher (sauf ceux appartenant à une catégorie cachée)" required=true help="Par exemple les membres inscrits à la lettre d'information."}
			{input type="radio-btn" name="target_type" value="all" label="Tous les membres (sauf ceux appartenant à une catégorie cachée)" required=true}
			{input type="radio-btn" name="target_type" value="category" label="Membres d'une seule catégorie" required=true}
			{input type="radio-btn" name="target_type" value="service" label="Membres inscrits à une activité, et à jour" required=true help="Les membres dont l'inscription a expiré ne recevront pas de message."}
			{input type="radio-btn" name="target_type" value="search" label="Membres renvoyés par une recherche enregistrée" required=true}
		</dl>
	</fieldset>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="step2" label="Continuer" shape="right" class="main"}
	</p>
{elseif $target_type == 'field'}
	<fieldset>
		<legend>Quel champ de la fiche membre&nbsp;?</legend>
		<dl>
			{foreach from=$list item="field"}
				{input type="radio" name="target_value" value=$field.name label=$field.label help="%d membres"|args:$field.count}
				{input type="hidden" name="labels[%s]"|args:$field.name default=$field.label}
			{/foreach}
		</dl>
	</fieldset>
{elseif $target_type == 'category'}
	<fieldset>
		<legend>Quelle catégorie&nbsp;?</legend>
		<dl>
			{foreach from=$list item="cat"}
				{input type="radio" name="target_value" value=$cat.id label=$cat.name help="%d membres"|args:$cat.count}
				{input type="hidden" name="labels[%s]"|args:$cat.id default=$cat.name}
			{/foreach}
		</dl>
	</fieldset>
{elseif $target_type == 'service'}
	<fieldset>
		<legend>Quelle activité&nbsp;?</legend>
		<dl>
			{foreach from=$list item="service"}
				{input type="radio" name="target_value" value=$service.id label=$service.label help="%d membres"|args:$service.nb_users_ok}
				{input type="hidden" name="labels[%s]"|args:$service.id default=$service.label}
			{/foreach}
		</dl>
	</fieldset>
{elseif $target_type == 'search'}
	<fieldset>
		<legend>Quelle recherche utiliser&nbsp;?</legend>
		<dl>
			{foreach from=$list item="search"}
				{input type="radio" name="target_value" value=$search.id label=$search.label help="%d membres"|args:$search.count}
				{input type="hidden" name="labels[%s]"|args:$search.id default=$search.label}
			{/foreach}
		</dl>
	</fieldset>
{/if}
{if $target_type}
	<p class="help"><small>Note : le nombre de membres affiché ne prend pas en compte les membres qui ne disposent pas d'adresse e-mail, ou qui se sont désinscrits. Le nombre de destinataires réels sera affiché avant envoi.</small></p>
	<p class="submit">
		{input type="hidden" name="subject"}
		{input type="hidden" name="target_type" default=$target_type}
		{csrf_field key=$csrf_key}
		{button type="submit" name="step3" label="Créer" shape="right" class="main"}
	</p>
{/if}
</form>

{include file="_foot.tpl"}