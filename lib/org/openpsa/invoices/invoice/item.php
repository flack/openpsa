<?php
/**
 * @package org.openpsa.invoices
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class
 *
 * @property integer $invoice
 * @property integer $task
 * @property integer $deliverable
 * @property integer $position
 * @property string $description
 * @property float $pricePerUnit
 * @property float $units
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_invoice_item_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_invoice_item';
    public $skip_invoice_update = false;

    public function _on_creating()
    {
        if (   $this->invoice
            && $this->position == 0) {
            $invoice = org_openpsa_invoices_invoice_dba::get_cached($this->invoice);
            $this->position = count($invoice->get_invoice_items()) + 1;
        }
        return true;
    }

    public function _on_created()
    {
        $this->_update_invoice();
    }

    public function _on_deleted()
    {
        $this->_update_invoice();
    }

    public function _on_updated()
    {
        $this->_update_invoice();
    }

    /**
     * Human-readable label for cases like Asgard navigation
     */
    public function get_label() : string
    {
        $label = $this->description;
        if (strlen($label) > 100) {
            $label = substr($label, 0, 97) . '...';
        }
        return $label;
    }

    public function render_link() : string
    {
        $url = '';
        $link = nl2br($this->description);

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');

        if ($projects_url = $siteconfig->get_node_full_url('org.openpsa.projects')) {
            try {
                $task = org_openpsa_projects_task_dba::get_cached($this->task);
                $url = $projects_url . 'task/' . $task->guid . '/';
            } catch (midcom_error $e) {
            }
        }
        if (   $url == ''
            && $sales_url) {
            try {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($this->deliverable);
                $url = $sales_url . 'deliverable/' . $deliverable->guid . '/';
            } catch (midcom_error $e) {
            }
        }
        if ($url != '') {
            $link = '<a href="' . $url . '">' . $link . '</a>';
        }
        return $link;
    }

    private function _update_invoice()
    {
        if (!$this->skip_invoice_update) {
            try {
                //update the invoice-sum so it will always contain the actual sum
                $invoice = new org_openpsa_invoices_invoice_dba($this->invoice);
                $old_sum = $invoice->sum;
                self::update_invoice($invoice);
                if (   $old_sum != $invoice->sum
                    && !empty($this->deliverable)) {
                    $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($this->deliverable);
                    self::update_deliverable($deliverable);
                }
            } catch (midcom_error $e) {
                $e->log();
            }
        }
    }

    public static function get_sum(array $constraints)
    {
        if (empty($constraints)) {
            throw new midcom_error('Invalid constraints given');
        }

        $sum = 0;

        $mc = self::new_collector();
        foreach ($constraints as $field => $value) {
            $mc->add_constraint($field, '=', $value);
        }

        $results = $mc->get_rows(['units', 'pricePerUnit']);
        foreach ($results as $result) {
            $sum += $result['units'] * $result['pricePerUnit'];
        }

        return $sum;
    }

    public static function update_deliverable(org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        $invoiced = self::get_sum(['deliverable' => $deliverable->id]);

        if ($invoiced != $deliverable->invoiced) {
            $deliverable->invoiced = $invoiced;
            if (   $deliverable->orgOpenpsaObtype !== org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
                && $deliverable->state < org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED) {
                $deliverable->state = org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED;
            }
            $deliverable->update();
        }
    }

    public static function update_invoice(org_openpsa_invoices_invoice_dba $invoice)
    {
        $invoice_sum = self::get_sum(['invoice' => $invoice->id]);
        $invoice_sum = round($invoice_sum, 2);
        if ($invoice_sum != round($invoice->sum, 2)) {
            $invoice->sum = $invoice_sum;
            $invoice->update();
        }
    }
}
