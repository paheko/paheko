{include file="_head.tpl" title="Nouveau message collectif" current="users/mailing"}

{form_errors}

<form method="post" action="" data-focus=1>
{if !$target}
	<fieldset>
		<legend>Sujet du message</legend>
		<dl>
			{input type="text" required="true" label="Sujet du message" name="subject" class="full-width"}
		</dl>
	</fieldset>
	<fieldset>
		<legend>Qui doit recevoir ce message&nbsp;?</legend>
		<dl>
		{foreach from=$mailing_fields item=$field}
			{input type="radio-btn" name="target" value="field_%s"|args:$field.name label="%s (%s membres)"|args:$field.label:$field.count required=true help="Les membres appartenant à une catégorie cachée ne sont pas inclus."}
		{/foreach}
			{input type="radio-btn" name="target" value="all" label="Tous les membres (sauf ceux appartenant à une catégorie cachée)" required=true}
			{input type="radio-btn" name="target" value="category" label="Les membres d'une seule catégorie" required=true}
			{input type="radio-btn" name="target" value="service" label="Les membres inscrits à une activité, et à jour" required=true help="Les membres dont l'inscription a expiré ne recevront pas de message."}
			{input type="radio-btn" name="target" value="search" label="Les membres renvoyés par une recherche enregistrée" required=true}
		</dl>
	</fieldset>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="step2" label="Continuer" shape="right" class="main"}
	</p>
{elseif $target == 'category'}
	<fieldset>
		<legend>Quelle catégorie&nbsp;?</legend>
		<dl>
			{foreach from=$categories item="cat"}
				{input type="radio" name="target_id" value=$cat.id label=$cat.name help="%d membres"|args:$cat.count}
			{/foreach}
		</dl>
	</fieldset>
{elseif $target == 'service'}
	<fieldset>
		<legend>Quelle activité&nbsp;?</legend>
		<dl>
			{foreach from=$services->iterate() item="service"}
				{input type="radio" name="target_id" value=$service.id label=$service.label help="%d membres"|args:$service.nb_users_ok}
			{/foreach}
		</dl>
	</fieldset>
{elseif $target == 'search'}
	<fieldset>
		<legend>Quelle recherche utiliser&nbsp;?</legend>
		<dl>
			{foreach from=$search_list item="search"}
				{input type="radio" name="target_id" value=$search.id label=$search.label help="%d membres"|args:$search.count}
			{/foreach}
		</dl>
	</fieldset>
{/if}
{if $target}
	<p class="help"><small>Note : le nombre de membres affiché ne prend pas en compte les membres qui ne disposent pas d'adresse e-mail, ou qui se sont désinscrits. Le nombre de destinataires réels sera affiché avant envoi.</small></p>
	<p class="submit">
		{input type="hidden" name="subject"}
		{input type="hidden" name="target"}
		{csrf_field key=$csrf_key}
		{button type="submit" name="step3" label="Créer" shape="right" class="main"}
	</p>
{/if}
</form>

{include file="_foot.tpl"}