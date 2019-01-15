<?php
$project = $data['object'];
$project->get_members();
$view = $data['object_view'];
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="content-with-sidebar">
<div class="main org_openpsa_projects_project">
    <h1><?php echo $data['l10n']->get('project'); ?>: &(view['title']:h);</h1>

  <div class="midcom_helper_datamanager2_view">
    <div class="field status <?php echo $project->status_type; ?>">
        <?php echo '<div class="title">' . $data['l10n']->get('project status') . ': </div>';
        echo '<div class="value">' . $data['l10n']->get($project->status_type) . '</div>';
        ?>
    </div>
    <div class="field">
        <?php
        echo '<div class="title">' . $data['l10n']->get('timeframe') . ': </div>';
        echo '<div class="value">' . $data['l10n']->get_formatter()->timeframe($project->start, $project->end, 'date') . '</div>';
        ?>
    </div>
    <div class="field">
        <?php echo '<div class="title">' . $data['l10n']->get('description') . ': </div>';
        echo '<div class="value">' . $view['description'] . '</div>';
        ?>
    </div>
  </div>

    <?php
    midcom::get()->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "task/list/project/{$project->guid}/");
    ?>
</div>

<aside>
    <?php
    try {
        $customer = org_openpsa_contacts_group_dba::get_cached($project->customer);
        echo "<h2>" . $data['l10n']->get('customer') . "</h2>\n";

        $customer_html = $customer->official;

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts')) {
            $customer_html = '<a href="' . $contacts_url . '/group/' . $customer->guid . '/">' . $customer_html . "</a>\n";
        }
        echo $customer_html;
    } catch (midcom_error $e) {
    }

    if ($project->manager) {
        echo "<h2>" . $data['l10n']->get('manager') . "</h2>\n";
        $contact = org_openpsa_widgets_contact::get($project->manager);
        echo $contact->show_inline();
    }
    if (!empty($project->resources)) {
        echo "<h2>" . $data['l10n']->get('resources') . "</h2>\n";
        foreach (array_keys($project->resources) as $contact_id) {
            $contact = org_openpsa_widgets_contact::get($contact_id);
            echo $contact->show_inline() . " ";
        }
    }

    if (!empty($project->contacts)) {
        echo "<h2>" . $data['l10n']->get('contacts') . "</h2>\n";
        foreach (array_keys($project->contacts) as $contact_id) {
            $contact = org_openpsa_widgets_contact::get($contact_id);
            $contact->show();
        }
    }
    ?>
</aside>
</div>
