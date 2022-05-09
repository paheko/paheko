{include file="admin/_head.tpl" title="Export des membres" current="membres"}

{include file="admin/membres/_nav.tpl" current="export"}

{include file="admin/membres/import_export_nav.tpl" current="export"}

<form method="post" action="{$self_url}">
  <fieldset>
      <legend>Exporter</legend>
      <dl>
          <dt><label for="f_type">Type de fichier</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
          <dd>
              <input type="radio" name="format" id="f_type_csv" value="csv" {form_field name=format checked="csv" default="csv"} />
              <label for="f_type_csv">Fichier CSV générique</label>
          </dd>
          <dd>
              <input type="radio" name="format" id="f_type_ods" value="ods" {form_field name=format checked="ods"} />
              <label for="f_type_ods">Fichier classeur Office</label>
          </dd>
      </dl>
  </fieldset>
  <p class="submit">
      {csrf_field key=$csrf_key}
      {button type="submit" name="export" label="Exporter" shape="upload" class="main"}
  </p>
</form>

{include file="admin/_foot.tpl"}
