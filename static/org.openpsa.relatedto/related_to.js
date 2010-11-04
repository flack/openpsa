var ooRelatedInfoDisplayed = Array();
var ooRelatedInfoFetched = Array();

function ooToggleRelatedInfoDisplay(guid)
{
    if (ooRelatedInfoDisplayed[guid] != true)
    {
        detailArea = document.getElementById('org_openpsa_relatedto_details_' + guid);
        ooRelatedInfoDisplayed[guid] = true;

        if (ooRelatedInfoFetched[guid] != true)
        {
            ooFetchRelatedInfo(guid);
            ooRelatedInfoFetched[guid] = true;
        }
        else
        {
            jQuery('#org_openpsa_relatedto_details_' + guid).show('slow');
        }
    }
    else
    {
        detailArea = document.getElementById('org_openpsa_relatedto_details_' + guid);
        jQuery('#org_openpsa_relatedto_details_' + guid).hide('slow');
        /*detailArea.style.display = 'none';*/
        ooRelatedInfoDisplayed[guid] = false;
    }
}

function ooFetchRelatedInfo(guid)
{
    var urlField = document.getElementById('org_openpsa_relatedto_details_url_' + guid);
    var targetField = document.getElementById('org_openpsa_relatedto_details_' + guid);
    if (urlField)
    {
        targetField.style.display = 'block';
        /*Effect.Grow('org_openpsa_relatedto_details_'+guid);*/
        ooAjaxGet(urlField.title, false, targetField, 'ooPopulateRelatedInfo', 10000, 'text');
    }
}

function ooPopulateRelatedInfo(results, element)
{
    element.className = 'ajax_ready';
    element.style.display = 'none';
    /*Effect.BlindUp(element);*/
    element.innerHTML = results;
    jQuery(element).show('slow');
}

function ooRelatedDenyConfirm(prefix, mode, guid)
{
    url = prefix + 'relatedto/ajax/' + mode + '/' + guid;
    if (mode == 'deny')
    {
        element = document.getElementById('org_openpsa_relatedto_line_' + guid);
    }
    else
    {
        element = document.getElementById('org_openpsa_relatedto_toolbar_confirmdeny_' + guid);
    }
    ooAjaxPost(url, '', element, false, 'ooRelatedDenyConfirm_callback');
}

function ooRelatedDenyConfirm_callback(response, element)
{
    jQuery(element).hide('slow');
}
