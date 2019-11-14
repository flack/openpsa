<?php
$view = $data['view_campaign'];
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
$member_url = $node[MIDCOM_NAV_ABSOLUTEURL] . 'campaign/members/' . $data['campaign']->guid . '/';

$grid = $data['grid'];
$grid->add_pager(30)
    ->set_option('height', 600)
    ->set_option('viewrecords', true)
    ->set_option('url', $member_url)
    ->set_option('sortname', 'index_lastname');

$grid->set_option('caption', $data['l10n']->get('recipients'));

$grid->set_column('lastname', $data['l10n']->get('lastname'), 'classes: "title ui-ellipsis"', 'string')
    ->set_column('firstname', $data['l10n']->get('firstname'), 'width: 100, classes: "ui-ellipsis"', 'string')
    ->set_column('email', $data['l10n']->get('email'), 'width: 100, classes: "ui-ellipsis"', 'string')
    ->set_column('delete', $data['l10n_midcom']->get('delete'), 'width: 20, align: "center", classes: "delete grid-button"')
    ->set_column('bounced', $data['l10n']->get('bounced recipients'), 'width: 20, align: "center", classes: "bounce-status warn"');
?>
<div class="content-with-sidebar">
<div class="main org_openpsa_directmarketing_campaign">
    <h1>&(view['title']:h);</h1>

    <?php
    if ($data['campaign']->archived) {
        $date = $data['l10n']->get_formatter()->date($data['campaign']->archived);
        echo "<p class=\"archived\">" . sprintf($data['l10n']->get('archived on %s'), $date) . "</p>\n";
    }
    ?>

    &(view['description']:h);
    <?php
    echo '<h2>' . $data['l10n']->get('testers') . '</h2>';

    $data['campaign']->get_testers();
    if (!empty($data['campaign']->testers)) {
        $testers = [];
        foreach (array_keys($data['campaign']->testers) as $id) {
            $person = org_openpsa_widgets_contact::get($id);
            $testers[] = $person->show_inline();
        }
        echo implode(', ', $testers);
    } else {
        echo "<strong>" . $data['l10n']->get('no testers') . "</strong>";
    } ?>
    <div class="org_openpsa_directmarketing full-width fill-height">
      <?php $grid->render(); ?>
    </div>

    <script type="text/javascript">
    $('#<?php echo $grid->get_identifier();?>').on('click', 'td.delete i', function(event) {
        var guid = $(event.target).data('person-guid'),
            member_guid = $(event.target).data('member-guid'),
            post_data = {org_openpsa_ajax_mode: 'unsubscribe', org_openpsa_ajax_person_guid: guid};

        $(event.target).removeClass('fa-trash').addClass('fa-spin fa-spinner');
        $.post('&(node[MIDCOM_NAV_ABSOLUTEURL]);campaign/unsubscribe/ajax/' + member_guid + '/', post_data, function() {
            $('#<?php echo $grid->get_identifier();?>').trigger('reloadGrid');
            $('#org_openpsa_widgets_contact-' + guid).fadeOut('fast', function() {
                $('#org_openpsa_widgets_contact-' + guid).remove();
            });
        });
    });
    </script>
</div>
<aside>
    <?php
    midcom::get()->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "message/list/campaign/{$data['campaign']->guid}");
    ?>
</aside>
</div>