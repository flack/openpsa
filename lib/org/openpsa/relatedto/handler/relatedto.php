<?php
/**
 * @package org.openpsa.relatedto
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * relatedto ajax/ahah handler
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_handler_relatedto extends midcom_baseclasses_components_handler
{
    /**
     * The object we're working with
     */
    private $_object = null;

    /**
     * The mode we're in
     *
     * @var string
     */
    private $_mode = null;

    /**
     * The sort order
     *
     * @var string
     */
    private $_sort = 'default';

    /**
     * The link array
     *
     * @var array
     */
    private $_links = [
        'incoming' => [],
        'outgoing' => []
    ];

    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('org.openpsa.relatedto');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_render($handler_id, array $args, array &$data)
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);
        $this->_mode = $args[1];
        if (isset($args[2])) {
            $this->_sort = $args[2];
        }

        switch ($this->_mode) {
            case 'in':
                $this->_get_object_links($this->_links['incoming'], $this->_object, true);
                break;
            case 'out':
                $this->_get_object_links($this->_links['outgoing'], $this->_object, false);
                break;
            case 'both':
                $this->_get_object_links($this->_links['incoming'], $this->_object, true);
                $this->_get_object_links($this->_links['outgoing'], $this->_object, false);
                break;
            default:
                throw new midcom_error('Mode ' . $this->_mode . ' not supported');
        }

        $this->_prepare_request_data();
    }

    private function _prepare_request_data()
    {
        org_openpsa_relatedto_plugin::add_header_files();
        $ref = midcom_helper_reflector::get($this->_object);
        $object_label = $ref->get_object_label($this->_object);

        if ($object_url = midcom::get()->permalinks->create_permalink($this->_object->guid)) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => $object_url,
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('back'),
                MIDCOM_TOOLBAR_GLYPHICON => 'eject',
            ]);
            $this->add_breadcrumb($object_url, $object_label);
        }
        $this->add_breadcrumb("", $this->_l10n->get('view related information'));

        // Load "Create X" buttons for all the related info
        $relatedto_button_settings = org_openpsa_relatedto_plugin::common_toolbar_buttons_defaults();

        $class_label = $ref->get_class_label();
        $date_label = $this->_l10n->get_formatter()->datetime();
        $relatedto_button_settings['wikinote']['wikiword'] = str_replace('/', '-', sprintf($this->_l10n->get('notes for %s %s on %s'), $class_label, $object_label, $date_label));

        org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $this->_object, $this->_request_data['topic']->component, $relatedto_button_settings);
        org_openpsa_relatedto_plugin::add_journal_entry_button($this->_view_toolbar, $this->_object->guid);

        $this->_request_data['object'] = $this->_object;
        $this->_request_data['show_title'] = ($this->_mode == 'both');
    }

    /**
     * Get object's relatedtos
     *
     * @param array &$arr
     * @param midcom_core_dbaobject $obj
     * @param boolean $inbound True means toGuid == $obj->guid, false fromGuid == $obj->guid
     */
    private function _get_object_links(array &$arr, midcom_core_dbaobject $obj, $inbound = true)
    {
        if ($inbound) {
            $object_prefix = 'from';
            $mc = org_openpsa_relatedto_dba::new_collector('toGuid', $obj->guid);
        } else {
            $object_prefix = 'to';
            $mc = org_openpsa_relatedto_dba::new_collector('fromGuid', $obj->guid);
        }

        $mc->add_value_property($object_prefix . 'Guid');
        $mc->add_value_property($object_prefix . 'Class');
        $mc->add_value_property($object_prefix . 'Component');
        $mc->add_value_property('status');
        $mc->add_constraint('status', '<>', org_openpsa_relatedto_dba::NOTRELATED);
        $mc->execute();
        $links = $mc->list_keys();

        foreach (array_keys($links) as $guid) {
            //TODO: check for duplicates ?
            $to_arr = [
                'link' => [
                    'guid' => $guid,
                    'component' => $mc->get_subkey($guid, $object_prefix . 'Component'),
                    'class' => $mc->get_subkey($guid, $object_prefix . 'Class'),
                    'status' => $mc->get_subkey($guid, 'status')
                ]
            ];
            try {
                $to_arr['other_obj'] = midcom::get()->dbfactory->get_object_by_guid($mc->get_subkey($guid, $object_prefix . 'Guid'));
            } catch (midcom_error $e) {
                continue;
            }

            $to_arr['sort_time'] = $this->_get_object_links_sort_time($to_arr['other_obj']);
            $arr[] = $to_arr;
        }
    }

    /**
     * returns a unix timestamp for sorting relatedto arrays
     */
    private function _get_object_links_sort_time(midcom_core_dbaobject $obj)
    {
        switch (true) {
            case $obj instanceof org_openpsa_calendar_event_dba:
            case $obj instanceof org_openpsa_projects_task_dba:
                return $obj->start;
            default:
                return $obj->metadata->created;
        }
    }

    /**
     * Renders the selected view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_render($handler_id, array &$data)
    {
        midcom_show_style('relatedto_start');

        $this->_show_list('incoming');
        $this->_show_list('outgoing');

        midcom_show_style('relatedto_end');
    }

    private function _show_list($direction)
    {
        if (count($this->_links[$direction]) < 1) {
            return;
        }
        $this->_request_data['direction'] = $direction;

        //Sort the array of links
        uasort($this->_links[$direction], [$this, '_sort_by_time']);

        midcom_show_style('relatedto_list_top');

        foreach ($this->_links[$direction] as $linkdata) {
            $this->_render_line($linkdata['link'], $linkdata['other_obj']);
        }

        midcom_show_style('relatedto_list_bottom');
    }

    /**
     * Code to sort array by key 'sort_time', from smallest to greatest
     */
    private function _sort_by_time($a, $b)
    {
        $ap = $a['sort_time'];
        $bp = $b['sort_time'];
        if ($this->_sort == 'reverse') {
            return $bp - $ap;
        }
        return $ap - $bp;
    }

    /**
     * Renders single link line
     *
     * @param array $link The necessary link information
     * @param object &$other_obj The link target
     */
    private function _render_line(array $link, &$other_obj)
    {
        $this->_request_data['link'] = $link;
        $this->_request_data['other_obj'] =& $other_obj;

        $ref = midcom_helper_reflector::get($link['class']);

        $this->_request_data['icon'] = $ref->get_object_icon($other_obj);

        if (get_class($other_obj) != $link['class']) {
            $other_obj = new $link['class']($other_obj);
        }

        switch ($link['class']) {
            case net_nemein_wiki_wikipage::class:
                $this->_render_line_wikipage($other_obj);
                break;
            case org_openpsa_calendar_event_dba::class:
                $this->_render_line_event($other_obj);
                break;
            case org_openpsa_projects_task_dba::class:
            case org_openpsa_projects_project::class:
                $this->_render_line_task($other_obj);
                break;
            case org_openpsa_documents_document_dba::class:
                $this->_render_line_document($other_obj);
                break;
            case org_openpsa_sales_salesproject_dba::class:
                $this->_render_line_salesproject($other_obj);
                break;
            case org_openpsa_invoices_invoice_dba::class:
                $this->_render_line_invoice($other_obj);
                break;
            default:
                $this->_render_line_default($link, $other_obj);
                break;
        }
    }

    private function get_node_url($component)
    {
        return org_openpsa_core_siteconfig::get_instance()->get_node_full_url($component);
    }

    /**
     * Renders a document line
     *
     * @param org_openpsa_documents_document_dba $other_obj The link target
     */
    private function _render_line_document(org_openpsa_documents_document_dba $other_obj)
    {
        $this->_request_data['document_url'] = midcom::get()->permalinks->create_permalink($other_obj->guid);

        midcom_show_style('relatedto_list_item_document');
    }

    /**
     * Renders a wikipage line
     *
     * @param net_nemein_wiki_wikipage $other_obj The link target
     */
    private function _render_line_wikipage(net_nemein_wiki_wikipage $other_obj)
    {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($other_obj->topic);
        if (!$node) {
            // The page isn't from this site
            return;
        }

        $this->_request_data['page_url'] = $node[MIDCOM_NAV_FULLURL];

        midcom_show_style('relatedto_list_item_wikipage');
    }

    /**
     * Renders an event line
     *
     * @param object $other_obj The link target
     */
    private function _render_line_event($other_obj)
    {
        $this->_request_data['raw_url'] = '';

        $title = $other_obj->title;

        if ($url = $this->get_node_url('org.openpsa.calendar')) {
            //Calendar node found, render a better view
            $this->_request_data['raw_url'] = $url . 'event/raw/' . $other_obj->guid . '/';
            $workflow = $this->get_workflow('viewer');
            $title = '<a href="' . $url . 'event/' . $other_obj->guid .  '/" ' . $workflow->render_attributes() . '>' . $title . "</a>\n";
        }

        $this->_request_data['title'] = $title;

        midcom_show_style('relatedto_list_item_event');
    }

    /**
     * Renders a task line
     *
     * @param object $other_obj The link target
     */
    private function _render_line_task($other_obj)
    {
        if ($other_obj->orgOpenpsaObtype == org_openpsa_projects_task_dba::OBTYPE) {
            $type = 'task';
        } else {
            $type = 'project';
        }

        $title = $other_obj->title;

        if ($url = $this->get_node_url('org.openpsa.projects')) {
            $title = '<a href="' . $url . $type . '/' . $other_obj->guid  . '/" target="task_' . $other_obj->guid . '">' . $title . "</a>\n";
        }
        $this->_request_data['title'] = $title;
        $this->_request_data['type'] = $type;

        midcom_show_style('relatedto_list_item_task');
    }

    /**
     * Renders a sales project line
     *
     * @param org_openpsa_sales_salesproject_dba $other_obj The link target
     */
    private function _render_line_salesproject(org_openpsa_sales_salesproject_dba $other_obj)
    {
        $title = $other_obj->title;

        if ($url = $this->get_node_url('org.openpsa.sales')) {
            $title = '<a href="' . $url . 'salesproject/' . $other_obj->guid  . '/" target="salesproject_' . $other_obj->guid . '">' . $title . "</a>\n";
        }

        $this->_request_data['title'] = $title;

        midcom_show_style('relatedto_list_item_salesproject');
    }

    /**
     * Renders an invoice line
     *
     * @param org_openpsa_invoices_invoice_dba $other_obj The link target
     */
    private function _render_line_invoice(org_openpsa_invoices_invoice_dba $other_obj)
    {
        $title = $this->_i18n->get_string('invoice', 'org.openpsa.invoices') . ' ' . $other_obj->get_label();

        if ($url = $this->get_node_url('org.openpsa.invoices')) {
            $title = '<a href="' . $url . 'invoice/' . $other_obj->guid  . '/" target="invoice_' . $other_obj->guid . '">' . $title . "</a>\n";
        }
        $this->_request_data['title'] = $title;

        midcom_show_style('relatedto_list_item_invoice');
    }

    /**
     * Default line rendering, used if a specific renderer cannot be found
     *
     * Tries to find certain properties likely to hold semi-useful information about
     * the object, failing that outputs class and guid.
     *
     * @param array $link The necessary link information
     * @param object $other_obj The link target
     */
    private function _render_line_default(array $link, $other_obj)
    {
        $class = get_class($other_obj);

        $ref = midcom_helper_reflector::get($other_obj);
        $object_label = $ref->get_object_label($other_obj);
        if ($url = midcom::get()->permalinks->create_permalink($other_obj->guid)) {
            $object_label = '<a href="' . $url . '" target="' . $class . $this->_object->guid . '">' . $object_label . '</a>';
        }

        echo "            <li class=\"unknown {$class}\" id=\"org_openpsa_relatedto_line_{$link['guid']}\">\n";
        echo '                <span class="icon">' . $this->_request_data['icon'] . "</span>\n";
        echo '                <span class="title">' . $object_label . "</span>\n";
        echo '                <ul class="metadata">';
        echo '                    <li>' . $ref->get_class_label() . "</li>\n";
        echo "                </ul>\n";
        self::render_line_controls($link, $other_obj);
        echo "</li>\n";
    }

    /**
     * Renders (if necessary) controls for confirming/deleting link object
     *
     * @param array $link The necessary link information
     * @param object $other_obj The link target
     */
    public static function render_line_controls(array $link, $other_obj)
    {
        echo "<ul class=\"relatedto_toolbar\" data-link-guid=\"{$link['guid']}\" data-other-guid=\"{$other_obj->guid}\">\n";

        if (   $link['component'] == 'net.nemein.wiki'
            || $link['component'] == 'org.openpsa.calendar') {
            echo "<li><input type=\"button\" class=\"button info\" value=\"" . midcom::get()->i18n->get_string('details', 'org.openpsa.relatedto') . "\" /></li>\n";
        }

        if ($link['status'] == org_openpsa_relatedto_dba::SUSPECTED) {
            echo "<li><input type=\"button\" class=\"button confirm\" value=\"" . midcom::get()->i18n->get_string('confirm relation', 'org.openpsa.relatedto') . "\" /></li>\n";
            echo "<li><input type=\"button\" class=\"button deny\" value=\"" . midcom::get()->i18n->get_string('deny relation', 'org.openpsa.relatedto') . "\" /></li>\n";
        }

        echo '<li><input type="button" class="button delete" id="org_openpsa_relatedto_delete-' . $link['guid'] . '" value="' . midcom::get()->i18n->get_string('delete relation', 'org.openpsa.relatedto') . '" /></li>';
        echo "</ul>\n";
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_ajax($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $response = new midcom_response_xml;
        $response->result = false;

        //Request mode switch
        $this->_mode = $args[0];
        $this->_object = false;
        if (isset($args[1])) {
            try {
                $this->_object = midcom::get()->dbfactory->get_object_by_guid($args[1]);
                if (!($this->_object instanceof org_openpsa_relatedto_dba)) {
                    $response->status = "method '{$this->_mode}' requires guid of a link object as an argument";
                }
            } catch (midcom_error $e) {
                $response->status = "method '{$this->_mode}' requires guid of a link object as an argument";
            }
        }
        switch ($this->_mode) {
            case 'deny':
                $this->_object->status = org_openpsa_relatedto_dba::NOTRELATED;
                $response->result = $this->_object->update();
                $response->status = 'error:' . midcom_connection::get_error_string();
                break;
            case 'confirm':
                $this->_object->status = org_openpsa_relatedto_dba::CONFIRMED;
                $response->result = $this->_object->update();
                $response->status = 'error:' . midcom_connection::get_error_string();
                break;
            default:
                $response->status = "method '{$this->_mode}' not supported";
                break;
        }
        return $response;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        $response = new midcom_response_xml;

        try {
            $relation = new org_openpsa_relatedto_dba($args[0]);
            $response->result = $relation->delete();
            $response->status = 'Last message: ' . midcom_connection::get_error_string();
        } catch (midcom_error $e) {
            $response->result = false;
            $response->status = "Object '{$args[0]}' could not be loaded, error:" . $e->getMessage();
        }

        return $response;
    }
}
