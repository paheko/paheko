{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>{$legend}</legend>
        <dl>
            {input name="label" type="text" required=1 label="Libellé" source=$service}
            {input name="description" type="textarea" label="Description" source=$service}

            <dt><label for="f_periodicite_jours">Période de validité</label></dt>
            <dd class="help">Attention, une modification de la période renseignée ici ne modifie pas la date d'expiration des activités déjà enregistrées.</dd>
            {input name="period" type="radio" value="0" label="Pas de période (cotisation ponctuelle)" default=$period}
            {input name="period" type="radio" value="1" label="En nombre de jours" default=$period}
            <dd class="period_1">
                <dl>
                {input name="duration" type="number" step="1" label="Durée de validité" size="5" source=$service}
                </dl>
            </dd>
            {input name="period" type="radio" value="2" label="Période définie (date à date)" default=$period}
            <dd class="period_2">
                <dl class="periode_dates">
                    {input type="date" name="start_date" label="Date de début" source=$service}
                    {input type="date" name="end_date" label="Date de fin" source=$service}
                </dl>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key=$csrf_key}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

<script type="text/javascript">
{literal}
(function () {
    var hide = [];
    if (!$('#f_period_1').checked)
        hide.push('.period_1');

    if (!$('#f_period_2').checked)
        hide.push('.period_2');

    g.toggle(hide, false);

    function togglePeriod()
    {
        g.toggle(['.period_1', '.period_2'], false);

        if (this.checked && this.value == 1)
            g.toggle('.period_1', true);
        else if (this.checked && this.value == 2)
            g.toggle('.period_2', true);
    }

    $('#f_period_0').onchange = togglePeriod;
    $('#f_period_1').onchange = togglePeriod;
    $('#f_period_2').onchange = togglePeriod;
})();
{/literal}
</script>