<?php
$view = $data['view_campaign'];

$nap = new midcom_helper_nav();

$node = $nap->get_node($nap->get_current_node());
?>
<script type="text/javascript">
function org_openpsa_directmarketing_ajax_unsubscribe(person_guid, membership_guid)
{
    url = '&(node[MIDCOM_NAV_FULLURL]);campaign/unsubscribe/ajax/' + membership_guid;
    ooAjaxPost(url, 'org_openpsa_ajax_mode=unsubscribe&amp;org_openpsa_ajax_person_guid=' + person_guid, document.getElementById('org_openpsa_directmarketing_unsubscribe-' +  person_guid), false, 'org_openpsa_directmarketing_ajax_unsubscribe_callback');
}
function org_openpsa_directmarketing_ajax_unsubscribe_callback(response, element)
{
    newId = new String(element.id);
    regEx = /org_openpsa_directmarketing_unsubscribe-/;
    newId = newId.replace(regEx, 'org_openpsa_widgets_contact-');
    //alert('new id:' + newId.valueOf());
    contactwidget_div = document.getElementById(newId.valueOf());
    setTimeout('hideElement(document.getElementById("' + contactwidget_div.id + '"))', 2000);
}
function hideElement(element)
{
    element.style.display = 'none';
}
</script>

<div class="sidebar">
    <?php
    $_MIDCOM->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "message/list/campaign/{$data['campaign']->guid}");
    ?>
</div>

<div class="main org_openpsa_directmarketing_campaign">
    <h1>&(view['title']:h);</h1>

    <?php
    if ($data['campaign']->archived)
    {
        echo "<p class=\"archived\">" . sprintf($data['l10n']->get('archived on %s'), strftime('%x', $data['campaign']->archived)) . "</p>\n";
    }
    ?>

    &(view['description']:h);
    <?php
    echo '<h2>' . $data['l10n']->get('testers') . '</h2>';

    $data['campaign']->get_testers();
    if (count($data['campaign']->testers) > 0)
    {
        $counter = 1;
        foreach($data['campaign']->testers as $id => $bool)
        {
            $person = org_openpsa_widgets_contact::get($id);
            echo $person->show_inline();

            if (($counter++) < count($data['campaign']->testers))
            {
                echo ", ";
            }
        }
    }
    else
    {
        echo "<strong>" . $data['l10n']->get('no testers') . "</strong>";
    }
    ?>

    <?php
    if (   array_key_exists('campaign_members_count', $data)
        && $data['campaign_members_count'] > 0)
    {
        echo "<div class=\"area\">\n";
        echo "<h2>" . sprintf($data['l10n']->get('%d members'), $data['campaign_members_count']) . "</h2>\n";
        $data['campaign_members_qb']->show_pages();

        foreach ($data['campaign_members'] as $k => $member)
        {
            $contact = new org_openpsa_widgets_contact($member);

            //TODO: Localize, use proper constants, better icon for bounce etc
            $delete_string = sprintf($data['l10n']->get('remove %s from campaign'), $member->name);
            $contact->prefix_html .= '<input type="image" style="float: right;" src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/trash.png" class="delete" id="org_openpsa_directmarketing_unsubscribe-' . $member->guid . '" onclick="org_openpsa_directmarketing_ajax_unsubscribe(\'' . $member->guid . '\', \'' . $data['memberships'][$k]->guid . '\')" value="' . $delete_string . '" title="' . $delete_string . '" alt="' . $delete_string . '" />';
            if ($data['memberships'][$k]->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_BOUNCED)
            {
                $bounce_string = sprintf($data['l10n']->get('%s has bounced'), $member->email);
                $contact->prefix_html .= '<img style="float: right;" src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/repair.png" class="delete" id="org_openpsa_directmarketing_bounced-' . $member->guid . '" title="' . $bounce_string . '" alt="' . $bounce_string . '" />';
            }
            if (isset($contact->contact_details['id']) && $contact->contact_details['id'] > 0)
            {
                $contact->show();
            }
        }

        echo "</div>\n";
    }
    ?>
</div>
