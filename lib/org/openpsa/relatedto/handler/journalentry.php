<?php
/**
 * @package org.openpsa.relatedto
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * journal entry handler
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_handler_journalentry extends midcom_baseclasses_components_handler
{
    /**
     * contains the one journal_entry to edit
     */
    var $_journal_entry = null;

    /**
     * contains the query-builder for journal-entries
     */
    private $qb_journal_entries = null;
    /**
     * way the entries should be outputed
     */
    private $_output_mode = "html";

    /**
     * contains the controller for datamanager
     */
    private $_controller = null;
    /**
     * contains the datamanager
     */
    private $_datamanger = null;
    /**
     * contains the object the journal_entry is bind to
     */
    private $_current_object = null;

    function __construct()
    {
        parent::__construct();
        $_MIDCOM->style->prepend_component_styledir('org.openpsa.relatedto');
        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_entry($handler_id, $args, &$data)
    {
        $this->_current_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
        //passed guid does not exist
        if (empty($this->_current_object->guid))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to load object for passed guid: " . $args[0] . " Last Error was :" . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        if ($args[1])
        {
            $this->_output_mode = $args[1];
        }

        $this->_relocate_url = $_MIDCOM->permalinks->create_permalink($this->_current_object->guid);
        $this->_request_data['url_prefix'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";

         //add needed constraints etc. to the query-builder
        $this->qb_journal_entries = org_openpsa_relatedto_journal_entry_dba::new_query_builder();
        $this->qb_journal_entries->add_constraint('linkGuid' , '=' , $args[0]);
        $this->qb_journal_entries->add_order('followUp' , 'DESC');
        $this->_prepare_journal_query();

        $this->_request_data['entries'] = $this->qb_journal_entries->execute();
        $this->_request_data['object'] = $this->_current_object;
        $this->_request_data['page'] = 1;

        //because we only show entries of one object there is no need to show the object for every entry
        $this->_request_data['show_closed'] = true;
        $this->_request_data['show_object'] = false;

        if ($this->_output_mode == 'html')
        {
            $this->_prepare_output();
            org_openpsa_core_ui::enable_jqgrid();
            //pass url where to get the data for js-plugin
            $this->_request_data['data_url'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/" . $this->_current_object->guid ."/xml/";

            //prepare breadcrumb
            $ref = midcom_helper_reflector::get($this->_current_object);
            $object_label = $ref->get_object_label($this->_current_object);
            $object_url = $_MIDCOM->permalinks->create_permalink($this->_current_object->guid);
            if ($object_url)
            {
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => $object_url,
                    MIDCOM_NAV_NAME => $object_label
                );
            }
            $tmp[] = array
            (
                MIDCOM_NAV_URL => $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/render/" . $this->_current_object->guid . "/both/",
                MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('view related information', 'org.openpsa.relatedto')
            );
            $tmp[] = array
            (
                MIDCOM_NAV_URL => "",
                MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('journal entries', 'org.openpsa.relatedto')
            );
            $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
        }
        $this->_prepare_header();

        return true;
    }
    
    /**
     * function to add css & toolbar-items
     */
    private function _prepare_output()
    {
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $this->_relocate_url,
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('back'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $this->_request_data['url_prefix'] . "create/" . $this->_current_object->guid . "/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('add journal entry', 'org.openpsa.relatedto'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
            )
        );

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel'   => 'stylesheet',
                'type'  => 'text/css',
                'media' => 'all',
                'href'  => MIDCOM_STATIC_URL . "/org.openpsa.contactwidget/hcard.css",
            )
        );

    }

    function _show_entry($handler_id , &$data)
    {
        switch($this->_output_mode)
        {
            case 'html':
            case 'xml':
                midcom_show_style('show_entries_' . $this->_output_mode);
                break;
            default:
                midcom_show_style('show_entries_html');
                break;
        }
    }

    function _handler_create($handler_id, $args, &$data)
    {
        $this->_current_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);

        $this->_prepare_datamanager();
        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
            case 'cancel':
                $object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
                //relocate to relatedto-renders
                $add_url = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";
                $add_url = $add_url . $this->_current_object->guid . "/html/";
                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                $relocate = $_MIDCOM->permalinks->create_permalink($object->guid);
                $_MIDCOM->relocate($add_url);
                // This will exit.
                break;
        }
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;

        $this->_prepare_breadcrumb();

        return true;
    }

    function _show_create($handler_id, &$data)
    {
        midcom_show_style('journal_entry_edit');
    }

    /**
     * function to load libraries/schemas for datamanager
     */
    private function _prepare_datamanager()
    {
        $_MIDCOM->componentloader->load('org.openpsa.relatedto');

        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($GLOBALS['midcom_component_data']['org.openpsa.relatedto']['config']->get('schemadb_journalentry'));
        $this->_schema = 'default';

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (!$this->_datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Datamanager could not be instantiated.");
            // This will exit.
        }
    }
        /**
     * load controller for datamanager
     *
     * @access private
     */
    private function _load_controller()
    {
        if (!empty($this->_journal_entry))
        {
            $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        }
        else
        {
            $this->_controller = midcom_helper_datamanager2_controller::create('create');
        }
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->callback_object =& $this;
        if(!empty($this->_journal_entry))
        {
            $this->_controller->set_storage($this->_journal_entry, $this->_schema);
        }
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 create controller.");
            // This will exit.
        }
    }
    
    /**
     * Datamanager callback
     */
    function & dm2_create_callback(&$datamanager)
    {
        $reminder = new org_openpsa_relatedto_journal_entry_dba();
        $reminder->linkGuid = $this->_current_object->guid;
        if (! $reminder->create())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('We operated on this object:', $reminder);
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new reminder, cannot continue. Error: " . midcom_connection::get_error_string());
            // This will exit.
        }

        return $reminder;
    }
    
    function _handler_remove($handler_id, $args, &$data)
    {
        $this->_current_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
        $reminder = new org_openpsa_relatedto_journal_entry_dba($args[1]);

        $reminder->delete();

        $add_url = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/reminder/";
        $add_url = $add_url . $this->_current_object->guid . "/";

        $_MIDCOM->relocate($add_url);

        return true;
    }

    function _handler_edit($handler_id, $args, &$data)
    {
        $this->_journal_entry = new org_openpsa_relatedto_journal_entry_dba($args[0]);
        
        //passed guid does not exist
        if (empty($this->_journal_entry->guid))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to load object for passed guid: " . $args[0] . " Last Error was :" . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        
        $this->_current_object = $_MIDCOM->dbfactory->get_object_by_guid($this->_journal_entry->linkGuid);

        $this->_prepare_datamanager();
        $this->_load_controller();
        $url_prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";

        switch ($this->_controller->process_form())
        {
            case 'save':
            case 'cancel':
                $object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
                $url_prefix = $url_prefix . $this->_current_object->guid . "/html/";
                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                $relocate = $_MIDCOM->permalinks->create_permalink($object->guid);
                $_MIDCOM->relocate($url_prefix);
                // This will exit.
                break;
        }
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;

        org_openpsa_helpers::dm2_savecancel($this);
        //delete-button
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $url_prefix ."delete/" . $this->_journal_entry->guid . "/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_journal_entry->can_do('midgard:delete'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            )
        );

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/org.openpsa.core/ui-elements.css',
            )
        );

        $this->_prepare_breadcrumb();
        $_MIDCOM->bind_view_to_object($this->_journal_entry, $this->_controller->datamanager->schema->name);

        return true;
    }

    function _show_edit($handler_id, &$data)
    {
        midcom_show_style('journal_entry_edit');
    }

    function _handler_delete($handler_id, $args, &$data)
    {
        $this->_journal_entry = new org_openpsa_relatedto_journal_entry_dba($args[0]);
        $this->_current_object = $_MIDCOM->dbfactory->get_object_by_guid($this->_journal_entry->linkGuid);

        if(!$this->_journal_entry->delete())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to delete journal_entry: " . $args[0] . " Last Error was :" . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        //build url for relocate
        $url_prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/render/";
        $url_prefix = $url_prefix . $this->_current_object->guid . "/both/";

        $_MIDCOM->relocate($url_prefix);

        return true;
    }
    function _show_delete($handler_id, &$data)
    {

    }

    function _handler_list($handler_id , $args , &$data)
    {
        if (isset($args[0]))
        {
            $this->_output_mode = $args[0];
        }
        //output_mode different from html means we just want the data
        if ($this->_output_mode != 'html')
        {
            $this->qb_journal_entries = org_openpsa_relatedto_journal_entry_dba::new_query_builder();
            $this->qb_journal_entries->add_order('followUp' , 'DESC');
            $this->_prepare_journal_query();

            //show the corresponding object of the entry
            $this->_request_data['show_object'] = true;
            $this->_request_data['show_closed'] = false;
            $this->_request_data['page'] = 1;

            if (array_key_exists('show_closed' , $_POST))
            {
                $this->_request_data['show_closed'] = true;
            }
            $this->_request_data['entries'] = $this->qb_journal_entries->execute();


            //get the corresponding objects
            if($this->_request_data['show_object'] == true && !empty($this->_request_data['entries']))
            {
                $this->_request_data['linked_objects'] = array();
                $this->_request_data['linked_raw_objects'] = array();
                $_MIDCOM->componentloader->load('midcom.helper.reflector');

                foreach($this->_request_data['entries'] as $entry)
                {
                    if(array_key_exists($entry->linkGuid , $this->_request_data['linked_objects']))
                    {
                        continue;
                    }
                    //create reflector with linked object to get the right label
                    $linked_object = $_MIDCOM->dbfactory->get_object_by_guid($entry->linkGuid);
                    $reflector = new midcom_helper_reflector($linked_object);
                    $link_html = "<a href='" . $_MIDCOM->permalinks->create_permalink($linked_object->guid) . "'>" . $reflector->get_object_label($linked_object) ."</a>";
                    $this->_request_data['linked_objects'][$entry->linkGuid] = $link_html;
                    $this->_request_data['linked_raw_objects'][$entry->linkGuid] = $reflector->get_object_label($linked_object);
                }
            }
            //url_prefix to build the links to the entries
            $this->_request_data['url_prefix'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";
        }
        else
        {
            //url where the xml-data can be loaded
            $this->_request_data['data_url'] =$_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/list/xml/" ;
            //enable jqgrid for html-output
            org_openpsa_core_ui::enable_jqgrid();
        }
        $this->_prepare_header();

        return true;
    }

    function _show_list($handler_id, &$data)
    {
        midcom_show_style('show_entries_' . $this->_output_mode);
    }

    private function _prepare_header()
    {
        switch($this->_output_mode)
        {
            case 'xml':
                $_MIDCOM->header("Content-type: text/xml; charset=UTF-8");
                $_MIDCOM->skip_page_style = true;
                break;
        }
    }

    private function _prepare_journal_query()
    {
        if (array_key_exists('journal_entry_constraints' , $_POST))
        {
            foreach ($_POST['journal_entry_constraints'] as $constraint)
            {
                //"type-cast" for closed because it will be passed as string
                if($constraint['property'] == 'closed')
                {
                    if($constraint['value'] = 'false')
                    {
                        $constraint['value'] = false;
                    }
                    else
                    {
                        $constraint['value'] = true;
                    }
                }
                $this->qb_journal_entries->add_constraint($constraint['property'] , $constraint['operator'] , $constraint['value']);
            }
        }
        //check if there is a page & rows - parameter passed - if add them to qb
        if (array_key_exists('page' , $_POST) && array_key_exists('rows' , $_POST))
        {
            $this->_request_data['page'] = $_POST['page'];
            $this->qb_journal_entries->set_limit((int)$_POST['rows']);
            $offset = ((int)$_POST['page'] - 1) * (int)$_POST['rows'];
            $this->qb_journal_entries->set_offset($offset);
        }
    }
    /**
     * helper function to prepare the breadcrumb
     */
    private function _prepare_breadcrumb()
    {
        $tmp = Array();

        $ref = midcom_helper_reflector::get($this->_current_object);
        $object_label = $ref->get_object_label($this->_current_object);

        $tmp[] = array
        (
            MIDCOM_NAV_URL => $_MIDCOM->permalinks->create_permalink($this->_current_object->guid),
            MIDCOM_NAV_NAME => $object_label,
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . '__mfa/org.openpsa.relatedto/render/' . $this->_current_object->guid . '/both/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('view related information', 'org.openpsa.relatedto'),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "",
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('journal entry', 'org.openpsa.relatedto') . " : " . $object_label,
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }
}