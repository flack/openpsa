<?php
$view = $data['view_deliverable'];
$state = $data['deliverable']->get_state();
$formatter = $data['l10n']->get_formatter();
$per_unit = $data['l10n']->get('per unit');
if (   $data['product']
    && $unit_option = org_openpsa_products_viewer::get_unit_option($data['product']->unit)) {
    $per_unit = sprintf($data['l10n']->get('per %s'), $unit_option);
}
?>
<div class="org_openpsa_sales_salesproject_deliverable &(state);">
    <div class="sidebar">
        <?php if ($data['product']) {
                ?>
        <div class="products area">
            <?php
            echo "<h2>" . $data['l10n']->get('product') . "</h2>\n";
                echo $data['product']->render_link() . "\n"; ?>
        </div>
        <?php
            } ?>
    </div>

    <div class="main">
        <div class="tags">&(view['tags']:h);</div>
        <?php
        echo "<h1>" . $data['l10n']->get('single delivery') . ": {$data['deliverable']->title}</h1>\n";
        ?>

        <div class="midcom_helper_datamanager2_view">
            <div class="field">
                <div class="title"><?php echo $data['l10n']->get('state'); ?></div>
                <div class="value"><?php echo $data['l10n']->get($state); ?></div>
            </div>
                <?php if ($data['deliverable']->supplier) {
            ?>
                    <div class="field">
                        <div class="title"><?php echo $data['l10n']->get('supplier'); ?></div>
                        <div class="value">&(view['supplier']:h);</div>
                    </div>
                    <?php

        } ?>
                <div class="field">
                    <div class="title"><?php echo $data['l10n']->get('estimated delivery'); ?></div>
                    <div class="value">&(view['end']:h);</div>
                </div>
                <?php if ($data['deliverable']->notify) {
            ?>
                    <div class="field">
                        <div class="title"><?php echo $data['l10n']->get('notify date'); ?></div>
                        <div class="value"><?php echo $view['notify']; ?></div>
                    </div>
                    <?php

        } ?>
                <div class="field">
                    <div class="title"><?php echo $data['l10n_midcom']->get('description'); ?></div>
                    <div class="value">&(view['description']:h);</div>
                </div>
                <div class="field">
                    <div class="title"><?php echo $data['l10n']->get('pricing'); ?></div>
                    <div class="value">
                    <table class="list">
                      <thead>
                        <tr>
                          <th>&nbsp;</th>
                          <th><?php echo $per_unit ?></th>
                          <th><?php echo $data['l10n']->get('plan'); ?></th>
                          <th><?php echo $data['l10n']->get('is'); ?></th>
                          <th><?php echo midcom::get()->i18n->get_string('sum', 'org.openpsa.invoices'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td class="title"><?php echo $data['l10n']->get('price'); ?></td>
                          <td class="numeric"><?php echo $formatter->number($data['deliverable']->pricePerUnit); ?></td>
                          <td class="numeric"><?php echo $view['plannedUnits']; ?></td>
                          <td class="numeric"><?php echo $view['units']; ?></td>
                          <td class="numeric"><?php echo $formatter->number($data['deliverable']->price); ?></td>
                        </tr>
                        <tr>
                          <td class="title"><?php echo $data['l10n']->get('cost'); ?></td>
                          <?php
                          if ($data['deliverable']->costType == '%') {
                              ?>
                              <td class="numeric"><?php echo $view['costPerUnit']; ?> %</td>
                              <td class="numeric">&nbsp;</td>
                              <td class="numeric">&nbsp;</td>
                          <?php
                          } else {
                              ?>
                              <td class="numeric"><?php echo $formatter->number($data['deliverable']->costPerUnit); ?></td>
                              <td class="numeric"><?php echo $view['plannedUnits']; ?></td>
                              <td class="numeric"><?php echo $view['units']; ?></td>
                          <?php
                          } ?>
                              <td class="numeric"><?php echo $formatter->number($data['deliverable']->cost); ?></td>
                        </tr>
                      </tbody>
                    </table>
                <?php if ($data['deliverable']->invoiceByActualUnits) {
                              ?>
                    <ul>
                        <li><?php echo $data['l10n']->get('invoice by actual units'); ?></li>
                        <?php
                        if ($data['deliverable']->invoiceApprovedOnly) {
                            echo "<li>" . $data['l10n']->get('invoice approved only') . "</li>\n";
                        } ?>
                    </ul>
                    <?php
                          } ?>
                    </div>
                </div>
                <?php
                if ($data['deliverable']->invoiced > 0) {
                    ?>
                    <div class="field">
                        <div class="title"><?php echo $data['l10n']->get('invoiced'); ?></div>
                        <div class="value"><?php echo $formatter->number($data['deliverable']->invoiced); ?></div>
                    </div>
                    <?php

                }
                ?>
	    </div>
    </div>

    <div class="wide">
    <?php
    $tabs = array();
    if (   $data['invoices_url']
        && $data['deliverable']->invoiced > 0) {
        $tabs[] = array(
            'url' => $data['invoices_url'] . "list/deliverable/{$data['deliverable']->guid}/",
            'title' => midcom::get()->i18n->get_string('invoices', 'org.openpsa.invoices'),
        );
    }

    if (   $data['projects_url']
        && $data['deliverable']->state >= org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
        && $data['product']
        && $data['product']->orgOpenpsaObtype == org_openpsa_products_product_dba::TYPE_SERVICE) {
        $tabs[] = array(
            'url' => $data['projects_url'] . "task/list/all/agreement/{$data['deliverable']->id}/",
            'title' => midcom::get()->i18n->get_string('tasks', 'org.openpsa.projects'),
        );
    }
    org_openpsa_widgets_ui::render_tabs($data['deliverable']->guid, $tabs);
    ?>
    </div>
</div>