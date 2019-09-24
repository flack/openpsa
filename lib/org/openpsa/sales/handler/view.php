<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\helper\autocomplete;
use Symfony\Component\HttpFoundation\Request;

/**
 * Salesproject display class
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_view extends midcom_baseclasses_components_handler
{
    /**
     * The salesproject to display
     *
     * @var org_openpsa_sales_salesproject_dba
     */
    private $_salesproject;

    /**
     * @var array
     */
    private $deliverables = [];

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['salesproject'] = $this->_salesproject;

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['projects_url'] = $siteconfig->get_node_relative_url('org.openpsa.projects');

        $this->_request_data['products'] = $this->_list_products();
    }

    private function _list_products() : array
    {
        $mc = org_openpsa_products_product_dba::new_collector();

        $mc->add_order('productGroup');
        $mc->add_order('code');
        $mc->add_order('title');
        $mc->add_constraint('start', '<=', time());
        $mc->begin_group('OR');
            /*
             * List products that either have no defined end-of-market dates
             * or are still in market
             */
            $mc->add_constraint('end', '=', 0);
            $mc->add_constraint('end', '>=', time());
        $mc->end_group();

        return $mc->get_rows(['code', 'title', 'delivery', 'price', 'unit', 'productGroup'], 'id');
    }

    /**
     * Populate the toolbar
     */
    private function _populate_toolbar()
    {
        $buttons = [];
        if ($this->_salesproject->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager');
            $buttons[] = $workflow->get_button($this->router->generate('salesproject_edit', ['guid' => $this->_salesproject->guid]), [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }

        if ($this->_salesproject->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $this->_salesproject, 'recursive' => true]);
            $buttons[] = $workflow->get_button($this->router->generate('salesproject_delete', ['guid' => $this->_salesproject->guid]));
        }

        if (!empty($this->_request_data['projects_url'])) {
            $prefix = midcom_connection::get_url('self') . $this->_request_data['projects_url'];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $prefix . "project/{$this->_salesproject->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'),
                MIDCOM_TOOLBAR_GLYPHICON => 'tasks',
            ];
        }

        if ($this->_config->get('sales_pdfbuilder_class')) {
            if (   $this->_salesproject->can_do('midgard:update')
                && $this->is_pdf_creatable()) {
                $workflow = $this->get_workflow('datamanager');
                $buttons[] = $workflow->get_button($this->router->generate('create_offer', ['guid' => $this->_salesproject->guid]), [
                    MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                    MIDCOM_TOOLBAR_GLYPHICON => 'file-pdf-o',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create_offer'),
                ]);
            }
            $qb = org_openpsa_sales_salesproject_offer_dba::new_query_builder();
            $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
            $qb->add_order('metadata.revised', 'DESC');
            $this->_request_data['offers'] = $qb->execute();
        }

        $this->_view_toolbar->add_items($buttons);

        $relatedto_button_settings = org_openpsa_relatedto_plugin::common_toolbar_buttons_defaults();
        $formatter = $this->_l10n->get_formatter();
        $relatedto_button_settings['wikinote']['wikiword'] = str_replace('/', '-', sprintf($this->_l10n->get($this->_config->get('new_wikinote_wikiword_format')), $this->_salesproject->title, $formatter->datetime()));
        unset($relatedto_button_settings['task']);
        org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $this->_salesproject, $this->_component, $relatedto_button_settings);

        $this->bind_view_to_object($this->_salesproject);
    }

    private function is_pdf_creatable() : bool
    {
        if ($this->_salesproject->state != org_openpsa_sales_salesproject_dba::STATE_LOST) {
            $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
            $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
            $qb->add_constraint('up', '=', 0);
            $qb->add_constraint('state', '<', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
            return $qb->count() > 0;
        }
        return false;
    }

    /**
     * Looks up a salesproject to display.
     */
    public function _handler_view(string $guid, array &$data)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($guid);
        $this->set_active_leaf($this->_topic->id . ':' . $this->_salesproject->get_state());

        $data['view_salesproject'] = datamanager::from_schemadb($this->_config->get('schemadb_salesproject'))
            ->set_storage($this->_salesproject)
            ->get_content_html();
        $this->_list_deliverables();
        $this->_prepare_request_data();
        $this->_populate_toolbar();

        if ($customer = $this->_salesproject->get_customer()) {
            $this->add_breadcrumb($this->router->generate('list_customer', ['guid' => $customer->guid]), $customer->get_label());
        }

        $this->add_breadcrumb($this->router->generate('salesproject_edit', ['guid' => $this->_salesproject->guid]), $this->_salesproject->title);
        midcom::get()->metadata->set_request_metadata($this->_salesproject->metadata->revised, $this->_salesproject->guid);
        midcom::get()->head->set_pagetitle($this->_salesproject->title);

        autocomplete::add_head_elements();

        midcom::get()->head->enable_jquery_ui(['button']);
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/sales.js');
        midcom::get()->uimessages->add_head_elements();
    }

    /**
     * List all deliverables belonging to the current project
     */
    private function _list_deliverables()
    {
        $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
        $qb->add_constraint('up', '=', 0);

        $qb->add_order('state');
        $qb->add_order('metadata.created', 'DESC');
        foreach ($qb->execute() as $deliverable) {
            if (!array_key_exists($deliverable->get_state(), $this->deliverables)) {
                $this->deliverables[$deliverable->get_state()] = [];
            }
            $this->deliverables[$deliverable->get_state()][] = [
                'object' => $deliverable,
                'actions' => $this->render_actions($deliverable)
            ];
        }
    }

    /**
     * Shows the loaded salesproject.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        midcom_show_style('show-salesproject');
        midcom_show_style('show-salesproject-deliverables-header');

        foreach (['proposed', 'ordered', 'delivered', 'started', 'invoiced', 'declined'] as $state) {
            if (!empty($this->deliverables[$state])) {
                $data['state'] = $state;
                $data['deliverables'] = $this->deliverables[$state];
                midcom_show_style('show-salesproject-deliverables-grid');
            }
        }

        midcom_show_style('show-salesproject-deliverables-footer');
    }

    private function render_actions(org_openpsa_sales_salesproject_deliverable_dba $deliverable) : string
    {
        $actions = '';
        switch ($deliverable->get_state()) {
            case 'proposed':
                $actions .= '<button class="order"><i class="fa fa-check"></i>' . $this->_l10n->get('mark ordered') . '</button> ';
                $actions .= '<button class="decline"><i class="fa fa-ban"></i>' . $this->_l10n->get('mark declined') . '</button>';
                return $actions;
            case 'started':
            case 'ordered':
                if ($deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
                    $entries = $deliverable->get_at_entries();
                    if (   $entries
                        && $entries[0]->status == midcom_services_at_entry_dba::SCHEDULED
                        && midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_invoices_invoice_dba::class)) {
                        return '<button class="run_cycle"><i class="fa fa-refresh"></i>' . $this->_l10n->get('generate now') . '</button>';
                    }
                } elseif ($deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED) {
                    return '<button class="deliver"><i class="fa fa-check"></i>' . $this->_l10n->get('mark delivered') . '</button>';
                }
                break;
            case 'delivered':
                if (   $deliverable->orgOpenpsaObtype != org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
                    && midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_invoices_invoice_dba::class)) {
                    $client_class = $this->_config->get('calculator');
                    $client = new $client_class();
                    $client->run($deliverable);

                    if ($client->get_price() > 0) {
                        return '<button class="invoice"><i class="fa fa-file-text-o"></i>' . sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('invoice')) . '</button>';
                    }
                }
                break;
        }
        return '';
    }

    public function _handler_action(Request $request, string $action)
    {
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($request->request->getInt('id'));
        $deliverable->require_do('midgard:update');
        $old_state = $deliverable->get_state();
        $success = $deliverable->$action();
        $deliverable->refresh();

        if (!$success) {
            $messages = [
                'message' => 'Operation failed. Last Midgard error was: ' . midcom_connection::get_error_string()
            ];
        } else {
            $messages = [];
            foreach (midcom::get()->uimessages->get_messages() as $msg) {
                $messages[] = json_decode($msg, true);
            }
        }

        return new midcom_response_json([
            'success' => $success,
            'action' => $this->render_actions($deliverable),
            'new_status' => $deliverable->get_state(),
            'old_status' => $old_state,
            'messages' => $messages,
            'updated' => []
        ]);
    }
}
