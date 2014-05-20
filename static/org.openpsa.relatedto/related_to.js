var ooRelatedInfoFetched = Array();

function ooToggleRelatedInfoDisplay(guid)
{
    var target_field = $('#org_openpsa_relatedto_details_' + guid);
    if (!target_field.is('visible'))
    {
        if (ooRelatedInfoFetched[guid] != true)
        {
            var urlField = $('#org_openpsa_relatedto_details_url_' + guid);

            if (urlField.length > 0)
            {
                target_field.load(urlField.attr('title'), function()
                {
                    target_field.show('slow');
                });
            }
            ooRelatedInfoFetched[guid] = true;
        }
        else
        {
            $('#org_openpsa_relatedto_details_' + guid).show('slow');
        }
    }
    else
    {
        $('#org_openpsa_relatedto_details_' + guid).hide('slow');
    }
}

function ooRelatedDenyConfirm(prefix, mode, guid)
{
    url = prefix + '__mfa/org.openpsa.relatedto/ajax/' + mode + '/' + guid + '/';
    $.post(url, {}, function()
    {
        if (mode == 'deny')
        {
            $('#org_openpsa_relatedto_line_' + guid).hide('slow');
        }
        else
        {
            $('#org_openpsa_relatedto_toolbar_confirmdeny_' + guid).hide('slow');
        }
    });
}