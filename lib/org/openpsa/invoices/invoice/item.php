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
    var $skip_invoice_update = false;

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
            //update the invoice-sum so it will always contain the actual sum
            $invoice = new org_openpsa_invoices_invoice_dba($this->invoice);
            $invoice->sum = $invoice->get_invoice_sum();
            $invoice->update();
        }
    }

}
?>