<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="content-with-sidebar">
<div class="main">
    <?php
    // Display the group information
    foreach (array_filter($data['view']) as $fieldname => $fielddata) {
        switch ($fieldname) {
            case 'homepage':
                echo "<h2>" . $data['l10n']->get('contact information') . "</h2>\n";
                echo "<div><strong>" . $data['l10n']->get($fieldname) . ": </strong>";
                echo $fielddata . "</div>";
                break;
            case 'email':
                echo "<div><strong>" . $data['l10n']->get($fieldname) . ": </strong>";
                echo $fielddata . "</div>";
                break;
            case 'notes':
                echo "<h2>" . $data['l10n']->get('notes') . "</h2>\n";
                echo "<pre>" . $fielddata . "</pre>";
                break;
            case 'categories':
            case 'official':
            case 'company_id':
            case 'phone':
            case 'fax':
                echo "<div><strong>" . $data['l10n']->get($fieldname) . ": </strong>";
                echo $fielddata . "</div>";
                break;
        }
    }
    if (array_key_exists('billing_data', $data)) {
        echo "<h2>" . $data['l10n']->get('invoice defaults') . "</h2>\n";
        echo "<div><strong>" . midcom::get()->i18n->get_string('vat', 'org.openpsa.invoices') . ": </strong>";
        echo $data['billing_data']->vat . " %</div>\n";
        echo "<div><strong>" . midcom::get()->i18n->get_string('payment target', 'org.openpsa.invoices') . ": </strong>";
        echo $data['billing_data']->due . "</div>\n";
        $data['billing_data']->render_address();
    }
    org_openpsa_widgets_contact::show_address_card($data['group'], ['visiting', 'postal']);

    echo '<br style="clear:left" />';

    $siteconfig = org_openpsa_core_siteconfig::get_instance();

    $tabs = [];
    if (strpos($data['view']['categories'], $data['l10n']->get('client')) !== false) {
        //TODO: Check for privileges somehow
        if ($invoices_url = $siteconfig->get_node_relative_url('org.openpsa.invoices')) {
            $tabs[] = [
                'url' => $invoices_url . "list/customer/all/{$data['group']->guid}/",
                'title' => midcom::get()->i18n->get_string('invoices', 'org.openpsa.invoices'),
            ];
        }
        if ($sales_url = $siteconfig->get_node_relative_url('org.openpsa.sales')) {
            $tabs[] = [
                'url' => $sales_url . "list/customer/{$data['group']->guid}/",
                'title' => midcom::get()->i18n->get_string('salesprojects', 'org.openpsa.sales'),
            ];
        }
    }
    org_openpsa_widgets_ui::render_tabs($data['group']->guid, $tabs);
    ?>
</div>

<aside>
    <?php
    if ($data['parent_group']) {
        ?>
        <div class="area parent">
          <h2><?php printf($data['l10n']->get('%s of'), $data['l10n']->get($data['view']['organization_type'])); ?></h2>
            <dl>
                <dt><?php echo "<a href=\"{$node[MIDCOM_NAV_ABSOLUTEURL]}group/{$data['parent_group']->guid}/\">{$data['parent_group']->official}</a>"; ?></dt>
            </dl>
        </div>
        <?php

    }

    midcom::get()->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "group/members/" . $data['group']->guid . "/");
    midcom::get()->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "group/subgroups/" . $data['group']->guid . "/"); ?>

    <!-- TODO: Projects list, Add project button -->
</aside>
</div>