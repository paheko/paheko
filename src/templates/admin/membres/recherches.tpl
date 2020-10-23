{include file="admin/_head.tpl" title="Recherches enregistrées" current="membres"}

{include file="admin/membres/_nav.tpl" current="recherches"}

{form_errors}

{if $mode == 'edit'}
	<form method="post" action="{$self_url}">
		<fieldset>
			<legend>Modifier une recherche enregistrée</legend>
			<dl>
				<dt><label for="f_intitule">Intitulé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
				<dd><input type="text" name="intitule" id="f_intitule" value="{form_field name="intitule" data=$recherche}" size="80" required="required" /></dd>
				<dt>Statut</dt>
				<dd><label><input type="radio" name="prive" value="1" {if $recherche.id_membre}checked="checked"{/if} /> Recherche privée</label> — Visible seulement par moi-même</dd>
				<dd><label><input type="radio" name="prive" value="0" {if !$recherche.id_membre}checked="checked"{/if} /> Recherche publique</label> — Visible et exécutable par tous les membres ayant accès à la gestion des membres</dd>
				<dt>Type</dt>
				<dd>{if $recherche.type == Recherche::TYPE_JSON}Avancée{else}SQL{/if}</dd>
				<dt>Cible</dt>
				<dd>{$recherche.cible}</dd>
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key="edit_recherche_%s"|args:$recherche.id}
			<input type="submit" name="save" value="Enregistrer &rarr;" />
		</p>
	</form>
{elseif $mode == 'delete'}

	<form method="post" action="{$self_url}">
		<fieldset>
			<legend>Supprimer une recherche enregistrée</legend>
			<h3 class="warning">
				Êtes-vous sûr de vouloir supprimer la recherche enregistrée
				{$recherche.intitule}&nbsp;?
			</h3>
		</fieldset>

		<p class="submit">
			{csrf_field key="del_recherche_%s"|args:$recherche.id}
			<input type="submit" name="delete" value="Supprimer &rarr;" />
		</p>
	</form>
{elseif count($liste) == 0}
	<p class="alert">Aucune recherche enregistrée. <a href="{$admin_url}membres/recherche.php">Faire une nouvelle recherche</a></p>
{else}
	<table class="list">
		<thead>
			<tr>
				<th>Recherche</th>
				<th>Type</th>
				<th>Statut</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$liste item="recherche"}
			<tr>
				<th><a href="{$admin_url}membres/recherche.php?id={$recherche.id}">{$recherche.intitule}</a></th>
				<td>{if $recherche.type == Recherche::TYPE_JSON}Avancée{else}SQL{/if}</td>
				<td>{if !$recherche.id_membre}Publique{else}Privée{/if}</td>
				<td class="actions">
					{linkbutton href="membres/recherche.php?id=%d"|args:$recherche.id shape="search" label="Rechercher"}
					{if $recherche.id_membre || $session->canAccess('membres', Membres::DROIT_ADMIN)}
						{linkbutton href="membres/recherche.php?edit=%d"|args:$recherche.id shape="edit" label="Renommer"}
						{linkbutton href="membres/recherche.php?delete=%d"|args:$recherche.id shape="delete" label="Supprimer"}
					{/if}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

{include file="admin/_foot.tpl"}