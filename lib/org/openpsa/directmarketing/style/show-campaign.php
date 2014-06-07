<?php
$view = $data['view_campaign'];

$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="sidebar">
    <?php
    midcom::get()->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "message/list/campaign/{$data['campaign']->guid}");
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
        $testers = array();
        foreach (array_keys($data['campaign']->testers) as $id)
        {
            $person = org_openpsa_widgets_contact::get($id);
            $testers[] = $person->show_inline();
        }
        echo implode(', ', $testers);
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
        echo "<div>\n";
        echo "<h2>" . sprintf($data['l10n']->get('%d members'), $data['campaign_members_count']) . "</h2>\n";
        $data['campaign_members_qb']->show_pages();

        foreach ($data['campaign_members'] as $k => $member)
        {
            $contact = new org_openpsa_widgets_contact($member);

            //TODO: Localize, use proper constants, better icon for bounce etc
            $delete_string = sprintf($data['l10n']->get('remove %s from campaign'), $member->name);
            $contact->prefix_html .= '<input type="image" style="float: right;" src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/trash.png" class="delete" id="org_openpsa_directmarketing_unsubscribe-' . $member->guid . '" data-member-guid="' . $data['memberships'][$k]->guid . '" value="' . $delete_string . '" title="' . $delete_string . '" alt="' . $delete_string . '" />';
            if ($data['memberships'][$k]->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_member_dba::BOUNCED)
            {
                $bounce_string = sprintf($data['l10n']->get('%s has bounced'), $member->email);
                $contact->prefix_html .= '<img style="float: right;" src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/repair.png" class="delete" id="org_openpsa_directmarketing_bounced-' . $member->guid . '" title="' . $bounce_string . '" alt="' . $bounce_string . '" />';
            }
            if (!empty($contact->contact_details['id']))
            {
                $contact->show();
            }
        }

        echo "</div>\n";
    }
    ?>
</div>

<script type="text/javascript">
$('input.delete').bind('click', function(){
    var guid = this.id.substr(40),
    loading = "<img src='" + MIDCOM_STATIC_URL + "/stock-icons/32x32/ajax-loading.gif' alt='loading' />";
    member_guid = $(this).data('member-guid'),
    post_data = {org_openpsa_ajax_mode: 'unsubscribe', org_openpsa_ajax_person_guid: guid};

    $('#org_openpsa_widgets_contact-' + guid).css('text-align', 'center');
    $('#org_openpsa_widgets_contact-' + guid).html(loading);
    $.post('&(node[MIDCOM_NAV_ABSOLUTEURL]);campaign/unsubscribe/ajax/' + member_guid + '/', post_data, function()
    {
        $('#org_openpsa_widgets_contact-' + guid).fadeOut('fast', function()
        {
            $('#org_openpsa_widgets_contact-' + guid).remove();
        });
    });
});
</script>
