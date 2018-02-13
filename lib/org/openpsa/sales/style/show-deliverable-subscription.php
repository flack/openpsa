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
<div class="content-with-sidebar">
    <div class="main">
        <div class="tags">&(view['tags']:h);</div>
        <?php
        echo "<h1>" . $data['l10n']->get('subscription') . ": {$data['deliverable']->title}</h1>\n";
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
                    <div class="title"><?php echo $data['l10n']->get('subscription begins'); ?></div>
                    <div class="value">&(view['start']:h);</div>
                </div>
                <div class="field">
                    <div class="title"><?php echo $data['l10n']->get('subscription ends'); ?></div>
                    <div class="value">
                <?php
                if (!$data['deliverable']->continuous) {
                    echo $view['end'];
                } else {
                    echo $data['l10n']->get('continuous subscription');
                } ?>
                    </div>
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
                          <th colspan="2"><?php echo $per_unit ?></th>
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
                <div class="field">
                    <div class="title"><?php echo $data['l10n']->get('invoicing period'); ?></div>
                    <div class="value">&(view['unit']:h);</div>
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

    <aside>
        <?php if ($data['product']) {
                ?>
        <div class="products area">
            <?php
            echo "<h2>" . $data['l10n']->get('product') . "</h2>\n";
            echo $data['product']->render_link() . "\n"; ?>
        </div>
        <?php
            } ?>

        <div class="at area">
        <?php
        if ($at_entries = $data['deliverable']->get_at_entries()) {
            echo "<h2>" . $data['l10n']->get('next run') . "</h2>\n";
            echo "<table>\n";
            echo "    <thead>\n";
            echo "        <tr>\n";
            echo "            <th>" . midcom::get()->i18n->get_string('time', 'midcom.services.at') . "</th>\n";
            echo "            <th>" . midcom::get()->i18n->get_string('status', 'midcom.services.at') . "</th>\n";
            echo "        </tr>\n";
            echo "    </thead>\n";
            echo "    <tbody>\n";

            foreach ($at_entries as $entry) {
                echo "        <tr>\n";
                echo "            <td>" . $formatter->datetime($entry->start) . "</td>\n";

                echo "            <td>";
                switch ($entry->status) {
                    case midcom_services_at_entry_dba::SCHEDULED:
                        echo midcom::get()->i18n->get_string('scheduled', 'midcom.services.at');
                        break;
                    case midcom_services_at_entry_dba::RUNNING:
                        echo midcom::get()->i18n->get_string('running', 'midcom.services.at');
                        break;
                    case midcom_services_at_entry_dba::FAILED:
                        echo midcom::get()->i18n->get_string('failed', 'midcom.services.at');
                        break;
                }
                echo "</td>\n";

                echo "        </tr>\n";
            }
            echo "    </tbody>\n";
            echo "</table>\n";
        }
        ?>
        </div>
    </aside>
	</div>
    <div class="wide">
    <?php
    $tabs = [];
    if (   $data['invoices_url']
        && $data['deliverable']->invoiced > 0) {
        $tabs[] = [
            'url' => $data['invoices_url'] . "list/deliverable/{$data['deliverable']->guid}/",
            'title' => midcom::get()->i18n->get_string('invoices', 'org.openpsa.invoices'),
        ];
    }

    if (   $data['projects_url']
        && $data['deliverable']->state >= org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
        && $data['product']
        && $data['product']->orgOpenpsaObtype == org_openpsa_products_product_dba::TYPE_SERVICE) {
        $tabs[] = [
            'url' => $data['projects_url'] . "task/list/agreement/{$data['deliverable']->id}/",
            'title' => midcom::get()->i18n->get_string('tasks', 'org.openpsa.projects'),
        ];
    }
    org_openpsa_widgets_ui::render_tabs($data['deliverable']->guid, $tabs);
    ?>
    </div>
</div>