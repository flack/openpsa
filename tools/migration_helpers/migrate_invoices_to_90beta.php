<?php
/**
 * Converter script that transfers old-style relatedto connections to invoice_items. Handle with care!
 */
midcom::get()->auth->require_admin_user();
midcom::get()->disable_limits();

while (@ob_end_flush());
echo "<pre>\n";

$task_qb = org_openpsa_projects_task_dba::new_query_builder();
$tasks = $task_qb->execute();

foreach ($tasks as $task) {
    $relatedto_qb = org_openpsa_relatedto_dba::new_query_builder();
    $relatedto_qb->add_constraint('toGuid', '=', $task->guid);
    $relatedto_qb->add_constraint('fromClass', '=', 'org_openpsa_invoices_invoice_dba');
    $relatedtos = $relatedto_qb->execute();

    if (sizeof($relatedtos) == 0) {
        echo "Task " . $task->get_label() . " has no invoice relatedtos, skipping\n";
        flush();
    }

    foreach ($relatedtos as $relatedto) {
        $invoice = new org_openpsa_invoices_invoice_dba($relatedto->fromGuid);
        $items = $invoice->get_invoice_items();

        if (sizeof($items) == 0) {
            echo "Invoice " . $invoice->get_label() . " has no items, creating one for task\n";
            flush();

            $item = new org_openpsa_invoices_invoice_item_dba();
            $item->invoice = $invoice->id;
            $item->task = $task->id;
            $item->deliverable = $task->agreement;
            $item->pricePerUnit = $invoice->sum;
            $item->units = 1;
            $item->description = $task->title .  ' (auto-generated)';
            $item->create();
        } else {
            $found = false;
            foreach ($items as $item) {
                if ($item->task == $task->id) {
                    echo "Found invoice item for task " . $task->get_label() . ", setting deliverable\n";
                    flush();
                    $item->deliverable = $task->agreement;
                    $item->update();
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                foreach ($items as $item) {
                    if (   $item->description == $task->title
                        && $item->task == 0) {
                        echo "Found invoice item for task " . $task->get_label() . " by description, setting deliverable\n";
                        flush();
                        $item->deliverable = $task->agreement;
                        $item->update();
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                echo "Could not identify invoice item for task " . $task->get_label() . " on invoice " . $invoice->get_label() . ", creating empty item\n";
                flush();
                $item = new org_openpsa_invoices_invoice_item_dba();
                $item->invoice = $invoice->id;
                $item->task = $task->id;
                $item->deliverable = $task->agreement;
                $item->pricePerUnit = 0;
                $item->units = 1;
                $item->description = $task->title .  ' (auto-generated)';
                $item->create();
            }
        }

        $relatedto->delete();
    }
}

$deliverable_qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
$deliverables = $deliverable_qb->execute();

foreach ($deliverables as $deliverable) {
    $relatedto_qb = org_openpsa_relatedto_dba::new_query_builder();
    $relatedto_qb->add_constraint('toGuid', '=', $deliverable->guid);
    $relatedto_qb->add_constraint('fromClass', '=', 'org_openpsa_invoices_invoice_dba');
    $relatedtos = $relatedto_qb->execute();

    if (sizeof($relatedtos) == 0) {
        echo "Deliverable " . $deliverable->title . " has no invoice relatedtos, skipping\n";
        flush();
    }

    foreach ($relatedtos as $relatedto) {
        $invoice = new org_openpsa_invoices_invoice_dba($relatedto->fromGuid);
        $items = $invoice->get_invoice_items();

        if (sizeof($items) == 0) {
            echo "Invoice " . $invoice->get_label() . " has no items, creating one for deliverable\n";
            flush();

            $item = new org_openpsa_invoices_invoice_item_dba();
            $item->invoice = $invoice->id;
            $item->deliverable = $deliverable->id;
            $item->pricePerUnit = $invoice->sum;
            $item->units = 1;
            $item->description = $deliverable->title .  ' (auto-generated)';
            $item->create();
        } else {
            $found = false;
            foreach ($items as $item) {
                if ($item->deliverable == $deliverable->id) {
                    echo "Found invoice item for deliverable " . $deliverable->title . "\n";
                    flush();
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $cycle_number = (int) $invoice->get_parameter('org.openpsa.sales', 'cycle_number');
                $description = $deliverable->title;
                if ($cycle_number) {
                    $description .= ' ' . $cycle_number;
                }

                foreach ($items as $item) {
                    if (   $item->description == $description
                        && $item->deliverable == 0) {
                        echo "Found invoice item for deliverable " . $deliverable->title . " by description\n";
                        flush();
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                echo "Could not identify invoice item for deliverable " . $deliverable->title . " on invoice " . $invoice->get_label() . ", creating empty item\n";
                flush();
                $item = new org_openpsa_invoices_invoice_item_dba();
                $item->invoice = $invoice->id;
                $item->deliverable = $deliverable->id;
                $item->pricePerUnit = 0;
                $item->units = 1;
                $item->description = $deliverable->title .  ' (auto-generated)';
                $item->create();
            }
        }

        $relatedto->delete();
    }
}

$qb_items = org_openpsa_invoices_invoice_item_dba::new_query_builder();
$qb_items->add_constraint('task', '>', 0);
$qb_items->add_constraint('deliverable', '=', 0);
$items = $qb_items->execute();
foreach ($items as $item) {
    try {
        $task = new org_openpsa_projects_task_dba($item->task);
    } catch (midcom_error $e) {
        echo 'Failed to load task #' . $item->task . ': ' . $e->getMessage() . ", skipping\n";
        flush();
        continue;
    }
    try {
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($task->agreement);
    } catch (midcom_error $e) {
        echo 'Failed to load deliverable #' . $task->agreement . ': ' . $e->getMessage() . ", skipping\n";
        flush();
        continue;
    }
    $item->deliverable = $deliverable->id;
    if ($item->update()) {
        echo 'Updated item #' . $item->id . ' to deliverable ' . $deliverable->title . "\n";
        flush();
    } else {
        echo 'Failed to update item #' . $item->id . ': ' . midcom_connection::get_error_string() . "\n";
        flush();
    }
}

echo "</pre>\n";
