<?php
$view = $data['view_salesproject'];
$salesproject = $data['salesproject'];
$formatter = $data['l10n']->get_formatter();
?>
<div class="sidebar">
    <div class="contacts area">
        <?php
        if ($customer = $data['salesproject']->get_customer()) {
            echo "<h2>" . $data['l10n']->get('customer') . "</h2>\n";
            echo $customer->render_link();
        }
        if ($data['salesproject']->contacts) {
            echo "<h2>" . midcom::get()->i18n->get_string('contacts', 'org.openpsa.projects') . "</h2>\n";
            foreach (array_keys($data['salesproject']->contacts) as $contact_id) {
                $person_card = org_openpsa_widgets_contact::get($contact_id);
                $person_card->show();
            }
        } ?>
    </div>
    <?php
    $nap = new midcom_helper_nav();
    $node = $nap->get_node($nap->get_current_node());

    //TODO: Configure whether to show in/both and reverse vs normal sorting ?
    midcom::get()->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}__mfa/org.openpsa.relatedto/render/{$data['salesproject']->guid}/both/normal/");
    ?>
</div>

<div class="main salesproject">
<h1>&(view['title']:h);</h1>
<div class="midcom_helper_datamanager2_view">
    <div class="field">
     <div class="title"><?php echo $data['l10n']->get('code'); ?></div>
     <div class="value">&(view['code']:h);</div>
    </div>
    <div class="field">
     <div class="title"><?php echo $data['l10n']->get('state'); ?></div>
     <div class="value">&(view['state']:h);</div>
    </div>
    <div class="field">
     <div class="title"><?php echo $data['l10n_midcom']->get('description'); ?></div>
     <div class="value">&(view['description']:h);</div>
    </div>
    <?php if ($salesproject->state == org_openpsa_sales_salesproject_dba::STATE_ACTIVE) {
        ?>
        <div class="field">
         <div class="title"><?php echo $data['l10n']->get('estimated closing date'); ?></div>
         <div class="value">&(view['close_est']:h);</div>
        </div>
        <div class="field">
         <div class="title"><?php echo $data['l10n']->get('probability'); ?></div>
         <div class="value">&(view['probability']:h);</div>
        </div>
    <?php
    } ?>
    <div class="field">
     <div class="title"><?php echo $data['l10n']->get('value'); ?></div>
     <div class="value"><?php echo $formatter->number($salesproject->value); ?></div>
    </div>
    <div class="field">
     <div class="title"><?php echo $data['l10n']->get('profit'); ?></div>
     <div class="value"><?php echo $formatter->number($salesproject->profit); ?></div>
    </div>
    <?php
     $owner_card = org_openpsa_widgets_contact::get($salesproject->owner);
    ?>
    <div class="field">
     <div class="title"><?php echo $data['l10n']->get('owner'); ?></div>
     <div class="value"><?php echo $owner_card->show_inline(); ?></div>
    </div>
</div>
