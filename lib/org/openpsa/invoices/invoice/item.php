<?php
/**
 * @package org.openpsa.invoices
 * @copyright
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_invoice_item_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_invoice_item';
    public $skip_invoice_update = false;

    public function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        parent::__construct($id);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
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

    public function render_link()
    {
        $url = '';
        $link = nl2br($this->description);

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');
        $sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');

        if ($projects_url)
        {
            try
            {
                $task = org_openpsa_projects_task_dba::get_cached($this->task);
                $url = $projects_url . 'task/' . $task->guid . '/';
            }
            catch (midcom_error $e){}
        }
        if (   $url == ''
            && $sales_url)
        {
            try
            {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($this->deliverable);
                $url = $sales_url . 'deliverable/' . $deliverable->guid . '/';
            }
            catch (midcom_error $e){}
        }
        if ($url != '')
        {
            $link = '<a href="' . $url . '">' . $link . '</a>';
        }
        return $link;
    }

    private function _update_invoice()
    {
        if (!$this->skip_invoice_update)
        {
            try
            {
                //update the invoice-sum so it will always contain the actual sum
                $invoice = new org_openpsa_invoices_invoice_dba($this->invoice);
                $old_sum = $invoice->sum;
                self::update_invoice($invoice);
                if ($old_sum != $invoice->sum)
                {
                    $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($this->deliverable);
                    self::update_deliverable($deliverable);
                }
            }
            catch (midcom_error $e)
            {
                $e->log();
            }
        }
    }

    public static function update_deliverable(org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        $invoiced = self::_get_sum('deliverable', $deliverable->id);

        if ($invoiced != $deliverable->invoiced)
        {
            $deliverable->invoiced = $invoiced;
            $deliverable->update();
        }
    }

    public static function update_invoice(org_openpsa_invoices_invoice_dba $invoice)
    {
        $invoice_sum = self::_get_sum('invoice', $invoice->id);
        $invoice_sum = round($invoice_sum, 2);
        if ($invoice_sum != round($invoice->sum, 2))
        {
            $invoice->sum = $invoice_sum;
            $invoice->update();
        }
    }

    private static function _get_sum($field, $value)
    {
        $sum = 0;
        $mc = self::new_collector($field, $value);
        $mc->add_value_property('units');
        $mc->add_value_property('pricePerUnit');
        $mc->execute();
        $keys = $mc->list_keys();

        foreach ($keys as $key => $empty)
        {
            $sum += $mc->get_subkey($key, 'units') * $mc->get_subkey($key, 'pricePerUnit');
        }

        return $sum;
    }

    /**
     * Function which calculates the invoice_sum by invoice_items
     *
     * @param bool round - indicates if result should be rounded
     */
    function get_invoice_sum($round = true)
    {

        return $invoice_sum;
    }
}
?>