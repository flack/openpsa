<?php
/**
 * @package org.openpsa.invoices
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class, keep logic here
 *
 * @property integer $sent
 * @property integer $due
 * @property integer $paid
 * @property integer $date
 * @property integer $deliverydate
 * @property integer $number
 * @property string $description
 * @property float $sum
 * @property integer $vat
 * @property integer $cancelationInvoice
 * @property integer $customer
 * @property integer $customerContact
 * @property integer $owner Sender of the invoice
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_invoice_dba extends midcom_core_dbaobject implements org_openpsa_invoices_interfaces_customer
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_invoice';

    public array $autodelete_dependents = [
        org_openpsa_invoices_invoice_item_dba::class => 'invoice'
    ];

    private ?org_openpsa_invoices_billing_data_dba $_billing_data = null;

    public function get_status() : string
    {
        if ($this->id == 0) {
            return 'scheduled';
        }
        if ($this->cancelationInvoice) {
            return 'canceled';
        }
        if ($this->sent == 0) {
            return 'unsent';
        }
        if ($this->paid > 0) {
            return 'paid';
        }
        if ($this->due < time()) {
            return 'overdue';
        }
        return 'open';
    }

    public function get_icon() : string
    {
        return 'file-text-o';
    }

    public static function get_by_number(int $number) : ?self
    {
        $qb = self::new_query_builder();
        $qb->add_constraint('number', '=', $number);
        $result = $qb->execute();
        if (count($result) == 1) {
            return $result[0];
        }
        return null;
    }

    /**
     * Human-readable label for cases like Asgard navigation
     */
    public function get_label() : string
    {
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.invoices', 'config');
        return sprintf($config->get('invoice_number_format'), $this->number);
    }

    /**
     * Label property (for Asgard chooser and the likes)
     */
    public function get_label_property() : string
    {
        return 'number';
    }

    public function _on_creating() : bool
    {
        $this->_pre_write_operations();
        return true;
    }

    public function _on_updating() : bool
    {
        $this->_pre_write_operations();
        return true;
    }

    private function _pre_write_operations()
    {
        if ($this->sent > 0) {
            $time = time();
            if (!$this->date) {
                $this->date = $time;
            }
            if (!$this->deliverydate) {
                $this->deliverydate = $time;
            }
            if ($this->due == 0) {
                $this->due = ($this->get_default('due') * 3600 * 24) + $this->date;
            }
        }
    }

    public function _on_deleting() : bool
    {
        if (!midcom::get()->auth->request_sudo('org.openpsa.invoices')) {
            debug_add('Failed to get SUDO privileges, skipping invoice hour deletion silently.', MIDCOM_LOG_ERROR);
            return false;
        }

        $qb = self::new_query_builder();
        $qb->add_constraint('cancelationInvoice', '=', $this->id);
        foreach ($qb->execute() as $canceled) {
            $canceled->cancelationInvoice = 0;
            if (!$canceled->update()) {
                debug_add("Failed to remove cancelation reference from invoice #{$canceled->id}, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                return false;
            }
        }

        midcom::get()->auth->drop_sudo();
        return parent::_on_deleting();
    }

    /**
     * By default all authenticated users should be able to do
     * whatever they wish with relatedto objects, later we can add
     * restrictions on object level as necessary.
     */
    public function get_class_magic_default_privileges() : array
    {
        $privileges = parent::get_class_magic_default_privileges();
        $privileges['ANONYMOUS']['midgard:read'] = MIDCOM_PRIVILEGE_DENY;
        return $privileges;
    }

    /**
     * Get the default value for invoice
     */
    public function get_default(string $attribute)
    {
        $billing_data = $this->get_billing_data();
        return $billing_data->{$attribute};
    }

    /**
     * an invoice is cancelable if it is no cancelation invoice
     * itself and got no related cancelation invoice
     */
    public function is_cancelable() : bool
    {
        return (!$this->cancelationInvoice && !$this->get_canceled_invoice());
    }

    /**
     * returns the invoice that got canceled through this invoice, if any
     */
    public function get_canceled_invoice() : ?self
    {
        $qb = self::new_query_builder();
        $qb->add_constraint('cancelationInvoice', '=', $this->id);

        return $qb->get_result(0);
    }

    /**
     * Create & recalculate existing invoice_items by tasks
     */
    public function _recalculate_invoice_items()
    {
        $result_tasks = [];

        //get hour_reports for this invoice - mc ?
        $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', $this->id);

        // sums up the hours of hour_reports for each task
        foreach ($qb->execute() as $hour_report) {
            if (!array_key_exists($hour_report->task, $result_tasks)) {
                $result_tasks[$hour_report->task] = 0;
            }
            if ($hour_report->invoiceable) {
                $result_tasks[$hour_report->task] += $hour_report->hours;
            }
        }

        foreach ($result_tasks as $task_id => $hours) {
            $invoice_item = $this->_probe_invoice_item_for_task($task_id);

            $task = new org_openpsa_projects_task_dba($task_id);
            if ($agreement = $task->get_agreement()) {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($agreement);
                $invoice_item->pricePerUnit = $deliverable->pricePerUnit;
                $invoice_item->deliverable = $deliverable->id;
                //calculate price
                if (   $deliverable->invoiceByActualUnits
                    || $deliverable->plannedUnits == 0) {
                    $invoice_item->units = $hours;
                } else {
                    $invoice_item->units = $deliverable->plannedUnits;
                }
            } else {
                $invoice_item->units = $hours;
            }

            if ($invoice_item->description == '') {
                $invoice_item->description = $task->title;
            }

            $invoice_item->update();
        }
    }

    /**
     * Get corresponding invoice_items indexed by GUID
     *
     * @return org_openpsa_invoices_invoice_item_dba[]
     */
    public function get_invoice_items() : array
    {
        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', $this->id);
        $qb->add_order('position', 'ASC');

        $items = [];
        foreach ($qb->execute() as $item) {
            $items[$item->guid] = $item;
        }
        return $items;
    }

    /**
     * Get the billing data for the invoice
     */
    public function get_billing_data(bool $prioritize_contact = false) : org_openpsa_invoices_billing_data_dba
    {
        return $this->_billing_data ??= org_openpsa_invoices_billing_data_dba::get_by_object($this, $prioritize_contact);
    }

    public function get_customer(bool $prioritize_contact = false)
    {
        $fields = [
            org_openpsa_contacts_group_dba::class => 'customer',
            org_openpsa_contacts_person_dba::class => 'customerContact',
        ];

        if ($prioritize_contact) {
            $fields = array_reverse($fields);
        }
        
        foreach ($fields as $class => $property) {
            if (!empty($this->$property)) {
                try {
                    return $class::get_cached($this->$property);
                } catch (midcom_error $e) {
                    $e->log();
                }
            }
        }
        return null;
    }

    /**
     * Get invoice_item for the passed task id, if there is no item it will return a newly created one
     */
    private function _probe_invoice_item_for_task(int $task_id) : org_openpsa_invoices_invoice_item_dba
    {
        //check if there is already an invoice_item for this task
        $qb_invoice_item = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb_invoice_item->add_constraint('invoice', '=', $this->id);
        $qb_invoice_item->add_constraint('task', '=', $task_id);

        $invoice_items = $qb_invoice_item->execute();
        if (empty($invoice_items)) {
            $invoice_item = new org_openpsa_invoices_invoice_item_dba();
            $invoice_item->task = $task_id;
            $invoice_item->invoice = $this->id;
            $invoice_item->create();
        } else {
            $invoice_item = $invoice_items[0];
            if (count($invoice_items) > 1) {
                debug_add('More than one item found for task #' . $task_id . ', only returning the first', MIDCOM_LOG_INFO);
            }
        }

        return $invoice_item;
    }

    public function generate_invoice_number()
    {
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('calculator');
        $calculator = new $client_class;
        return $calculator->generate_invoice_number();
    }
}
