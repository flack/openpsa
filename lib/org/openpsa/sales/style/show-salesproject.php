<?php
$view = $data['view_salesproject'];
$salesproject = $data['salesproject'];
?>
<div class="salesproject">
<h1>&(view['title']:h);</h1>
    <div class="contacts">
        <?php
        if ($customer = $data['salesproject']->get_customer())
        {
            echo "<h2>{$customer->get_label()}</h2>\n";
        }
        $contacts = $data['salesproject']->contacts;
        foreach ($contacts as $contact_id => $active)
        {
            $person_card = org_openpsa_widgets_contact::get($contact_id);
            $person_card->show();
        }
        ?>
    </div>
    <table class="info">
    <tr>
     <th><?php echo $data['l10n']->get('code'); ?></th>
     <td>&(view['code']);</td>
    </tr>
    <tr>
     <th><?php echo $data['l10n']->get('status'); ?></th>
     <td>&(view['status']);</td>
    </tr>
    <tr>
     <th><?php echo $data['l10n_midcom']->get('description'); ?></th>
     <td>&(view['description']:h);</td>
    </tr>
    <?php if ($salesproject->status == org_openpsa_sales_salesproject_dba::STATUS_ACTIVE)
    { ?>
        <tr>
         <th><?php echo $data['l10n']->get('estimated closing date'); ?></th>
         <td>&(view['close_est']);</td>
        </tr>
        <tr>
         <th><?php echo $data['l10n']->get('probability'); ?></th>
         <td>&(view['probability']);</td>
        </tr>
    <?php } ?>
    <tr>
     <th><?php echo $data['l10n']->get('value'); ?></th>
     <td><?php echo org_openpsa_helpers::format_number($salesproject->value); ?></td>
    </tr>
    <tr>
     <th><?php echo $data['l10n']->get('profit'); ?></th>
     <td><?php echo org_openpsa_helpers::format_number($salesproject->profit); ?></td>
    </tr>
    <?php
     $owner_card = org_openpsa_widgets_contact::get($salesproject->owner);
    ?>
    <tr>
     <th><?php echo $data['l10n']->get('owner'); ?></th>
     <td><?php echo $owner_card->show_inline(); ?></td>
    </tr>

    </table>
