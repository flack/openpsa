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
    var $realcomponent = false;

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
    private $_sort = null;

    /**
     * The link array
     *
     * @var array
     */
    private $_links = null;

    public function __construct()
    {
        parent::__construct();
        midcom::get('style')->prepend_component_styledir('org.openpsa.relatedto');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_render($handler_id, array $args, array &$data)
    {
        $this->_object = midcom::get('dbfactory')->get_object_by_guid($args[0]);
        $this->_mode = $args[1];
        $this->_sort = 'default';
        if (isset($args[2]))
        {
            $this->_sort = $args[2];
        }

        $this->_links = array
        (
            'incoming' => array(),
            'outgoing' => array(),
        );

        switch ($this->_mode)
        {
            case 'in-paged':
                //Fall-trough intentional
            case 'in':
                $this->_get_object_links_in($this->_links['incoming'], $this->_object);
                break;
            case 'out-paged':
                //Fall-trough intentional
            case 'out':
                $this->_get_object_links_out($this->_links['outgoing'], $this->_object);
                break;
            case 'both-paged':
                //Fall-trough intentional
            case 'both':
                $this->_get_object_links_in($this->_links['incoming'], $this->_object);
                $this->_get_object_links_out($this->_links['outgoing'], $this->_object);
                break;
            default:
                throw new midcom_error('Mode ' . $this->_mode . ' not supported');
        }

        $this->_prepare_request_data();
    }

    private function _prepare_request_data()
    {
        org_openpsa_relatedto_plugin::add_header_files();

        $object_url = midcom::get('permalinks')->create_permalink($this->_object->guid);

        if ($object_url)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $object_url,
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('back'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
                )
            );
        }
        // Load "Create X" buttons for all the related info
        $relatedto_button_settings = org_openpsa_relatedto_plugin::common_toolbar_buttons_defaults();

        $ref = midcom_helper_reflector::get($this->_object);
        $class_label = $ref->get_class_label();
        $object_label = $ref->get_object_label($this->_object);

        $relatedto_button_settings['wikinote']['wikiword'] = str_replace('/', '-', sprintf(midcom::get('i18n')->get_string('notes for %s %s on %s', 'org.openpsa.relatedto'), $class_label, $object_label, strftime('%x %H:%M')));

        org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $this->_object, $this->_request_data['topic']->component, $relatedto_button_settings);

        org_openpsa_relatedto_plugin::add_journal_entry_button($this->_view_toolbar, $this->_object->guid);

        $this->_request_data['object'] =& $this->_object;

        $this->_request_data['show_title'] = false;
        if (   $this->_mode == 'both'
            || $this->_mode == 'both-paged')
        {
            $this->_request_data['show_title'] = true;
        }

        if ($object_url)
        {
            $this->add_breadcrumb($object_url, $object_label);
        }
        $this->add_breadcrumb("", $this->_l10n->get('view related information'));
    }


    /**
     * Default method for getting object's relatedtos (inbound ie toGuid == $obj->guid)
     *
     * Components handlers may need to override this to account
     * for specific object types and possible traversing of their children
     */
    private function _get_object_links_in(&$arr, $obj)
    {
        if (   !is_object($obj)
            || !is_array($arr))
        {
            return false;
        }
        $mc = org_openpsa_relatedto_dba::new_collector('toGuid', $obj->guid);
        $mc->add_value_property('fromGuid');
        $mc->add_value_property('fromClass');
        $mc->add_value_property('fromComponent');
        $mc->add_value_property('status');
        $mc->add_constraint('status', '<>', org_openpsa_relatedto_dba::NOTRELATED);
        $mc->execute();
        $links = $mc->list_keys();
        if (!is_array($links))
        {
            return false;
        }
        foreach ($links as $guid => $link)
        {
            //TODO: check for duplicates ?
            $to_arr = array('link' => false, 'other_obj' => false, 'sort_time' => false);

            $to_arr['link'] = array
            (
                'guid' => $guid,
                'component' => $mc->get_subkey($guid, 'fromComponent'),
                'class' => $mc->get_subkey($guid, 'fromClass'),
                'status' => $mc->get_subkey($guid, 'status')
            );
            try
            {
                $to_arr['other_obj'] = midcom::get('dbfactory')->get_object_by_guid($mc->get_subkey($guid, 'fromGuid'));
            }
            catch (midcom_error $e)
            {
                continue;
            }

            $to_arr['sort_time'] = $this->_get_object_links_sort_time($to_arr['other_obj']);
            $arr[] = $to_arr;
        }
        return true;
    }

    /**
     * Default method for getting object's relatedtos (outbound ie fromGuid == $obj->guid)
     *
     * Components handlers may need to override this to account
     * for specific object types and possible traversing of their children
     */
    private function _get_object_links_out(&$arr, $obj)
    {
        if (   !is_object($obj)
            || !is_array($arr))
        {
            return false;
        }
        $mc = org_openpsa_relatedto_dba::new_collector('fromGuid', $obj->guid);
        $mc->add_value_property('toGuid');
        $mc->add_value_property('toClass');
        $mc->add_value_property('toComponent');
        $mc->add_value_property('status');
        $mc->add_constraint('status', '<>', org_openpsa_relatedto_dba::NOTRELATED);
        $mc->execute();
        $links = $mc->list_keys();
        if (!is_array($links))
        {
            return false;
        }

        foreach ($links as $guid => $link)
        {
            //TODO: check for duplicates ?
            $to_arr = array('link' => false, 'other_obj' => false, 'sort_time' => false);

            $to_arr['link'] = array
            (
                'guid' => $guid,
                'component' => $mc->get_subkey($guid, 'toComponent'),
                'class' => $mc->get_subkey($guid, 'toClass'),
                'status' => $mc->get_subkey($guid, 'status')
            );
            try
            {
                $to_arr['other_obj'] = midcom::get('dbfactory')->get_object_by_guid($mc->get_subkey($guid, 'toGuid'));
            }
            catch (midcom_error $e)
            {
                continue;
            }
            $to_arr['sort_time'] = $this->_get_object_links_sort_time($to_arr['other_obj']);
            $arr[] = $to_arr;
        }
        return true;
    }

    /**
     * returns a unix timestamp for sorting relatedto arrays
     *
     * If components need to return very specific values here they should override
     * this method to add their own handling and if they do not know what to do call this
     * via parent::_get_object_links_sort_time()
     */
    private function _get_object_links_sort_time($obj)
    {
        switch(true)
        {
            case midcom::get('dbfactory')->is_a($obj, 'midcom_db_event'):
                return $obj->start;
            case midcom::get('dbfactory')->is_a($obj, 'org_openpsa_projects_task_dba'):
                return $obj->start;
            default:
                return $obj->metadata->created;
        }
    }

    /**
     * Renders the selected view
     *
     * Due to this being a purecode component we can't use the MidCOM style engine
     * but operations are divided into overrideable methods as much as possible so
     * components then can override them and then use the style engine within their
     * own context.
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
        if (count($this->_links[$direction]) < 1)
        {
            return;
        }
        $this->_request_data['direction'] = $direction;

        //Sort the array of links
        $this->_sort_link_array($this->_links[$direction]);

        midcom_show_style('relatedto_list_top');

        foreach ($this->_links[$direction] as $linkdata)
        {
            $this->_render_line($linkdata['link'], $linkdata['other_obj']);
        }

        midcom_show_style('relatedto_list_bottom');
    }

    /**
     * Sorts the given link array based on $this->_sort
     */
    private function _sort_link_array(&$arr)
    {
        switch ($this->_sort)
        {
            case 'reverse':
                uasort($arr, array('self', '_sort_by_time_reverse'));
                break;
            case 'normal':
            case 'default':
            default:
                uasort($arr, array('self', '_sort_by_time'));
                break;
        }
    }

    /**
     * Code to sort array by key 'sort_time', from greatest to smallest
     *
     * Used by $this->_sort_link_array()
     */
    private static function _sort_by_time_reverse($a, $b)
    {
        $ap = $a['sort_time'];
        $bp = $b['sort_time'];
        if ($ap > $bp)
        {
            return -1;
        }
        if ($ap < $bp)
        {
            return 1;
        }
        return 0;
    }

    /**
     * Code to sort array by key 'sort_time', from smallest to greatest
     *
     * Used by $this->_sort_link_array()
     */
    private static function _sort_by_time($a, $b)
    {
        $ap = $a['sort_time'];
        $bp = $b['sort_time'];
        if ($ap > $bp)
        {
            return 1;
        }
        if ($ap < $bp)
        {
            return -1;
        }
        return 0;
    }

    /**
     * Renders single link line
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private function _render_line(&$link, &$other_obj)
    {
        $this->_request_data['link'] =& $link;
        $this->_request_data['other_obj'] =& $other_obj;

        $ref = midcom_helper_reflector::get($link['class']);

        $this->_request_data['icon'] = $ref->get_object_icon($other_obj);

        //Make sure we have the component classes available and
        //fallback to default renderer if not
        if (!midcom::get('componentloader')->load_graceful($link['component']))
        {
            $this->_render_line_default($link, $other_obj);
            return;
        }

        if (get_class($other_obj) != $link['class'])
        {
            $other_obj = new $link['class']($other_obj);
        }
        if (!is_object($other_obj))
        {
            //probably ACL prevents us from seeing anything about it
            return;
        }
        /* Load renderer based on to which class tree the object belongs to
           REMEMBER: to keep more complex rules above simpler ones, ESPECIALLY
           if the simple one can match part of the complex one */

        switch($link['class'])
        {
            case 'net_nemein_wiki_wikipage':
                if ($other_obj->parameter('net.nemein.wiki:emailimport', 'is_email'))
                {
                    self::_render_line_wikipage_email($link, $other_obj);
                }
                else
                {
                    $this->_render_line_wikipage($link, $other_obj);
                }
                break;
            case 'midcom_db_event':
                //Fall-trough intentional
            case 'org_openpsa_calendar_event_dba':
                $this->_render_line_event($link, $other_obj);
                break;
            case 'org_openpsa_projects_task_dba':
            case 'org_openpsa_projects_project':
                $this->_render_line_task($link, $other_obj);
                break;
            case 'org_openpsa_documents_document_dba':
                $this->_render_line_document($link, $other_obj);
                break;
            case 'org_openpsa_sales_salesproject_dba':
                $this->_render_line_salesproject($link, $other_obj);
                break;
            case 'org_openpsa_projects_hour_report_dba':
                self::_render_line_hour_report($link, $other_obj);
                break;
            case 'org_openpsa_invoices_invoice_dba':
                $this->_render_line_invoice($link, $other_obj);
                break;
            default:
                $this->_render_line_default($link, $other_obj);
                break;
        }
    }

    /**
     * If a component wishes to show hour_report lines it must override this method
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private static function _render_line_hour_report(&$link, &$other_obj)
    {
        return;
    }

    /**
     * Renders a document line
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private function _render_line_document(&$link, &$other_obj)
    {
        $this->_request_data['document_url'] = midcom::get('permalinks')->create_permalink($other_obj->guid);

        midcom_show_style('relatedto_list_item_document');
    }

    /**
     * Renders a wikipage line
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private static function _render_line_wikipage_email(&$link, &$other_obj)
    {
        echo "            <li class=\"note email\" id=\"org_openpsa_relatedto_line_{$link['guid']}\">\n";

        $nap = new midcom_helper_nav();
        $node = $nap->get_node($other_obj->topic);
        if (!$node)
        {
            // The page isn't from this site
            return;
        }
        $page_url = "{$node[MIDCOM_NAV_FULLURL]}{$other_obj->name}";

        echo "                <span class=\"title\"><a href=\"{$page_url}\" target=\"wiki_{$other_obj->guid}\">{$other_obj->title}</a></span>\n";

        // Start metadata UL
        echo "                <ul class=\"metadata\">\n";
        // Time
        echo '                    <li class="time">' . strftime('%x', $other_obj->metadata->created) . "</li>\n";
        // Author
        echo "                    <li class=\"members\">" . midcom::get('i18n')->get_string('sender', 'net.nemein.wiki') . ": ";
        $author_card = org_openpsa_widgets_contact::get($other_obj->metadata->creator);
        echo $author_card->show_inline()." ";
        echo "                    </li>\n";
        // Recipients
        self::_render_line_wikipage_email_recipients($other_obj);
        // End metadata UL
        echo "                </ul>\n";

        echo "                <div id=\"org_openpsa_relatedto_details_url_{$other_obj->guid}\" style=\"display: none;\" title=\"{$node[MIDCOM_NAV_FULLURL]}raw/{$other_obj->name}/\"></div>\n";
        echo "                <div id=\"org_openpsa_relatedto_details_{$other_obj->guid}\" class=\"details hidden\" style=\"display: none;\">\n";
        echo "                </div>\n";
        //TODO: get correct node and via it then handle details trough AHAH (and when we have node we can use proper link in page_url as well

        self::render_line_controls($link, $other_obj);
        echo "            </li>\n";
    }

    private static function _render_line_wikipage_email_recipients($page)
    {
        $seen_emails = array();
        $qb = org_openpsa_relatedto_dba::new_query_builder();
        $qb->add_constraint('fromGuid', '=', $page->guid);
        $qb->add_constraint('fromComponent', '=', 'net.nemein.wiki');
        $qb->add_constraint('toComponent', '=', 'org.openpsa.contacts');
        $qb->begin_group('OR');
            $qb->add_constraint('toClass', '=', 'midcom_db_person');
            $qb->add_constraint('toClass', '=', 'org_openpsa_contacts_person_dba');
        $qb->end_group();
        $qb->add_constraint('status', '<>', org_openpsa_relatedto_dba::NOTRELATED);
        $recipients = $qb->execute();
        echo "                    <li class=\"members\">" . midcom::get('i18n')->get_string('recipients', 'net.nemein.wiki') . ": ";
        foreach ($recipients as $recipient_link)
        {
            try
            {
                $recipient = new midcom_db_person($recipient_link->toGuid);
                $seen_emails[$recipient->email] = true;
            }
            catch (midcom_error $e)
            {
                continue;
            }
            $recipient_card = new org_openpsa_widgets_contact($recipient);
            echo $recipient_card->show_inline() . " ";
        }
        $other_emails = $page->listparameters('net.nemein.wiki:emailimport_recipients');
        if ($other_emails)
        {
            while ($other_emails->fetch())
            {
                $email = $other_emails->name;
                if (isset($seen_emails[$email]))
                {
                    continue;
                }
                echo $email . ' ';
                $seen_emails[$email] = true;
            }
        }
        echo "                    </li>\n";
    }

    /**
     * Renders a wikipage line
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private function _render_line_wikipage(&$link, &$other_obj)
    {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($other_obj->topic);
        if (!$node)
        {
            // The page isn't from this site
            return;
        }

        $this->_request_data['page_url'] = $node[MIDCOM_NAV_FULLURL];

        midcom_show_style('relatedto_list_item_wikipage');
    }


    /**
     * Renders an event line
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private function _render_line_event(&$link, &$other_obj)
    {
        $this->_request_data['raw_url'] = '';

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $calendar_url = $siteconfig->get_node_full_url('org.openpsa.calendar');

        $title = $other_obj->title;

        if ($calendar_url)
        {
            //Calendar node found, render a better view
            $this->_request_data['raw_url'] = $calendar_url . 'event/raw/' . $other_obj->guid . '/';
            $title = '<a href="' . $calendar_url . 'event/' . $other_obj->guid .  '/" target="event_' . $other_obj->guid . '">' . $title . "</a>\n";
        }

        $this->_request_data['title'] = $title;

        midcom_show_style('relatedto_list_item_event');
    }

    /**
     * Renders a task line
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private function _render_line_task(&$link, &$other_obj)
    {
        if ($other_obj->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_TASK)
        {
            $type = 'task';
        }
        else
        {
            $type = 'project';
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');

        $title = $other_obj->title;

        if ($projects_url)
        {
            $title = '<a href="' . $projects_url . $type . '/' . $other_obj->guid  . '/" target="task_' . $other_obj->guid . '">' . $other_obj->title . "</a>\n";
        }
        $this->_request_data['title'] = $title;

        $this->_request_data['type'] = $type;

        midcom_show_style('relatedto_list_item_task');
    }

    /**
     * Renders a sales project line
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private function _render_line_salesproject(&$link, &$other_obj)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');

        $title = $other_obj->title;

        if ($sales_url)
        {
            $title = '<a href="' . $sales_url . 'salesproject/' . $other_obj->guid  . '/" target="salesproject_' . $other_obj->guid . '">' . $title . "</a>\n";
        }

        $this->_request_data['title'] = $title;

        midcom_show_style('relatedto_list_item_salesproject');
    }

    /**
     * Renders an invoice line
     *
     * See the _show_render documentation for details about styling
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private function _render_line_invoice(&$link, &$other_obj)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $invoices_url = $siteconfig->get_node_full_url('org.openpsa.invoices');

        $title = midcom::get('i18n')->get_string('invoice', 'org.openpsa.invoices') . ' ' . $other_obj->get_label();

        if ($invoices_url)
        {
            $title = '<a href="' . $invoices_url . 'invoice/' . $other_obj->guid  . '/" target="invoice_' . $other_obj->guid . '">' . $title . "</a>\n";
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
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    private function _render_line_default(&$link, &$other_obj)
    {
        $class = get_class($other_obj);

        $object_url = midcom::get('permalinks')->create_permalink($other_obj->guid);

        $ref = midcom_helper_reflector::get($other_obj);
        $class_label = $ref->get_class_label();
        $object_label = $ref->get_object_label($other_obj);
        if ($object_url)
        {
            $object_label = '<a href="' . $object_url . '" target="' . $class . $this->_object->guid . '">' . $object_label . '</a>';
        }

        echo "            <li class=\"unknown {$class}\" id=\"org_openpsa_relatedto_line_{$link['guid']}\">\n";
        echo '                <span class="icon">' . $this->_request_data['icon'] . "</span>\n";
        echo '                <span class="title">' . $object_label . "</span>\n";
        echo '                <ul class="metadata">';
        echo '                    <li>' . $class_label . "</li>\n";
        echo "                </ul>\n";
        self::render_line_controls($link, $other_obj);
        echo "</li>\n";
    }

    /**
     * Renders (if necessary) controls for confirming/deleting link object
     *
     * @param array &$link The necessary link information
     * @param object &other_obj The link target
     */
    public static function render_line_controls(&$link, &$other_obj)
    {
        echo "<ul class=\"relatedto_toolbar\" id=\"org_openpsa_relatedto_toolbar_{$link['guid']}\">\n";

        switch ($link['component'])
        {
            case 'net.nemein.wiki':
            case 'org.openpsa.calendar':
                echo "<li><input type=\"button\" class=\"button\" id=\"org_openpsa_relatedto_details_button_{$other_obj->guid}\" onclick=\"ooToggleRelatedInfoDisplay('{$other_obj->guid}');\" class=\"info\" value=\"" . midcom::get('i18n')->get_string('details', 'org.openpsa.relatedto') . "\" /></li>\n";
                break;
        }

        if ($link['status'] == org_openpsa_relatedto_dba::SUSPECTED)
        {
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            echo "    <span id=\"org_openpsa_relatedto_toolbar_confirmdeny_{$link['guid']}\">\n";
            echo "        <li id=\"org_openpsa_relatedto_toolbar_confirm_{$link['guid']}\"><input type=\"button\" class=\"button\" value=\"" . midcom::get('i18n')->get_string('confirm relation', 'org.openpsa.relatedto') . "\" onclick=\"ooRelatedDenyConfirm('{$prefix}', 'confirm', '{$link['guid']}');\" /></li>\n";
            echo "        <li id=\"org_openpsa_relatedto_toolbar_deny_{$link['guid']}\"><input type=\"button\" class=\"button\" value=\"" . midcom::get('i18n')->get_string('deny relation', 'org.openpsa.relatedto') . "\" onclick=\"ooRelatedDenyConfirm('{$prefix}', 'deny', '{$link['guid']}');\" /><li>\n";
            echo "    </span>\n";
        }

        echo '<li><input type="button" class="button delete" id="org_openpsa_relatedto_delete-' . $link['guid'] . '" value="' . midcom::get('i18n')->get_string('delete relation', 'org.openpsa.relatedto') . '" /></li>';

        echo "</ul>\n";
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_ajax($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $response = new midcom_response_xml;
        $response->result = false;

        //Request mode switch
        $this->_mode =& $args[0];
        $this->_object = false;
        if (isset($args[1]))
        {
            try
            {
                $this->_object = midcom::get('dbfactory')->get_object_by_guid($args[1]);
                if (!($this->_object instanceof org_openpsa_relatedto_dba))
                {
                    $response->status = "method '{$this->_mode}' requires guid of a link object as an argument";
                }

            }
            catch (midcom_error $e)
            {
                $response->status = "method '{$this->_mode}' requires guid of a link object as an argument";
            }
        }
        switch ($this->_mode)
        {
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
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_ajax($handler_id, array &$data)
    {
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        $response = new midcom_response_xml;

        try
        {
            $relation = new org_openpsa_relatedto_dba($args[0]);
            $response->result = $relation->delete();
            $response->status = 'Last message: ' . midcom_connection::get_error_string();
        }
        catch (midcom_error $e)
        {
            $response->result = false;
            $response->status = "Object '{$args[0]}' could not be loaded, error:" . $e->getMessage();
        }

        return $response;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, array &$data){}
}
?>