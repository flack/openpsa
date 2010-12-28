<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice action handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_action extends midcom_baseclasses_components_handler
{
    /**
     * The invoice we're working with
     *
     * @param org_openpsa_invoices_invoice_dba
     */
    private $_object = null;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_mark_sent($handler_id, $args, &$data)
    {
        $this->_prepare_action($args);

        if (!$this->_object->sent)
        {
            $this->_object->sent = time();
            $this->_object->update();

            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked invoice "%s" sent'), $this->_object->get_label()), 'ok');

            $mc = new org_openpsa_relatedto_collector($this->_object->guid, 'org_openpsa_projects_task_dba');
            $tasks = $mc->get_related_objects();

            // Close "Send invoice" task
            foreach ($tasks as $task)
            {
                if (org_openpsa_projects_workflow::complete($task))
                {
                    $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked task "%s" finished'), $task->title), 'ok');
                }
            }
        }

        $this->_relocate();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_mark_paid($handler_id, $args, &$data)
    {
        $this->_prepare_action($args);

        if (!$this->_object->paid)
        {
            $this->_object->paid = time();
            $this->_object->update();

            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked invoice "%s" paid'), $this->_object->get_label()), 'ok');
        }

        $this->_relocate();
    }

    /**
     * Helper that prepares the action
     *
     * @return boolean Indicating success
     */
    private function _prepare_action(&$args)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            throw new midcom_error_forbidden('Only POST requests are allowed here.');
        }

        $_MIDCOM->auth->require_valid_user();

        $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);
        $this->_object->require_do('midgard:update');
    }

    /**
     * Helper that redirects after the action completed
     */
    private function _relocate()
    {
        if (isset($_GET['org_openpsa_invoices_redirect']))
        {
            $_MIDCOM->relocate($_GET['org_openpsa_invoices_redirect']);
            // This will exit
        }
        else
        {
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $_MIDCOM->relocate("{$prefix}invoice/{$this->_object->guid}/");
            // This will exit
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_itemedit($handler_id, $args, &$data)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);
        $this->_prepare_output();

        $relocate = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "invoice/" . $this->_object->guid . "/";

        if (isset($_POST['cancel']))
        {
            $_MIDCOM->relocate($relocate);
        }
        if (isset($_POST['save']))
        {
            if (array_key_exists('invoice_items', $_POST))
            {
                foreach ($_POST['invoice_items'] as $guid => $item)
                {
                    $update_item = new org_openpsa_invoices_invoice_item_dba($guid);
                    if (   array_key_exists('delete', $item)
                        && $item['delete'] == 'delete')
                    {
                        $update_item->delete();
                    }
                    //check if it really should be updated ?
                    else
                    {
                        $update_item->description = $item['description'];
                        $update_item->pricePerUnit = (float) str_replace(',', '.', $item['price_per_unit']);
                        $update_item->units = (float) str_replace(',', '.', $item['units']);
                        $update_item->update();
                    }
                }
            }
            $this->_create_invoice_items();
            //relocate to view
            $_MIDCOM->relocate($relocate);
        }
        //get invoice_items for this invoice
        $this->_request_data['invoice_items'] = $this->_object->get_invoice_items();
    }

    /**
     * Helper function to create invoice items from POST data
     */
    private function _create_invoice_items()
    {
        if (!array_key_exists('invoice_items_new', $_POST))
        {
            return;
        }
        foreach ($_POST['invoice_items_new'] as $item)
        {
            //check if needed properties are passed
            if(    !empty($item['description'])
                && !empty($item['price_per_unit'])
                && !empty($item['units']))
            {
                $new_item = new org_openpsa_invoices_invoice_item_dba();
                $new_item->invoice = $this->_object->id;
                $new_item->description = $item['description'];
                $new_item->pricePerUnit = (float) str_replace(',', '.', $item['price_per_unit']);
                $new_item->units = (float) str_replace(',', '.', $item['units']);
                $new_item->create();
            }
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_itemedit($handler_id, &$data)
    {
        midcom_show_style('show-items');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_recalculation($handler_id, $args, &$data)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);
        $relocate = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "invoice/itemedit/" . $this->_object->guid . "/";

        $this->_object->_recalculate_invoice_items();

        $_MIDCOM->relocate($relocate);
    }

    private function _prepare_output()
    {
        $this->add_stylesheet('/org.openpsa.core/list.css');

        $this->add_breadcrumb("invoice/" . $this->_object->guid . "/", $this->_l10n->get('invoice') . ' ' . $this->_object->get_label());
        $this->add_breadcrumb
        (
            "invoice/" . $this->_object->guid . "/",
            $this->_l10n->get('edit invoice items') . ': ' . $this->_l10n->get('invoice') . ' ' . $this->_object->get_label()
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "invoice/recalculation/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recalculate_by_reports'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_object),
            )
        );
        $_MIDCOM->enable_jquery();
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.invoices/invoice_item.js');
    }
}
?>