<?php
/**
 * @package org.openpsa.helpers
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Chooser create handler
 *
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers_handler_chooser extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The DBA class of the new object.
     *
     * @var string
     */
    private $_dbaclass = '';

    /**
     * The NAP node for the component the DBA class is from.
     *
     * @var array
     */
    private $_node = null;

    /**
     * The current action
     *
     * @var string
     */
    private $_action = 'form';

    /**
     * The Controller of the document used for creating
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The object we're working on, if any
     *
     * @param midcom_core_dbaobject
     */
    private $_object = null;

    public function __construct()
    {
        parent::__construct();
        $_MIDCOM->style->prepend_component_styledir('org.openpsa.helpers');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_create($handler_id, $args, &$data)
    {
        $this->_dbaclass = $args[0];
        $_MIDCOM->auth->require_user_do('midgard:create', null, $this->_dbaclass);

        $this->_load_component_node();

        $this->_controller = $this->get_controller('create');

        switch ($this->_controller->process_form())
        {
            case 'save':
                $this->_post_create_actions();
                //TODO: indexing
                $this->_action = 'save';
                break;
            case 'cancel':
                $this->_action = 'cancel';
                break;
        }

        $data['controller'] =& $this->_controller;
        $data['action'] =& $this->_action;

        $this->add_stylesheet("/org.openpsa.core/ui-elements.css");

        $_MIDCOM->skip_page_style = true;

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        return true;
    }

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_get_schemadb_snippet());
    }

    /**
     * This is what Datamanager calls to actually create an invoice
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $object = new $this->_dbaclass();

        if (!$object->create())
        {
            debug_print_r('We operated on this object:', $object);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new object, cannot continue. Error: " . midcom_connection::get_error_string());
            // This will exit.
        }

        $this->_object =& $object;

        return $object;
    }

    /**
     * Hook for post-create operations
     */
    private function _post_create_actions()
    {
        switch ($this->_dbaclass)
        {
            case 'org_openpsa_contacts_person_dba':
                $indexer = $_MIDCOM->get_service('indexer');
                org_openpsa_contacts_viewer::index_person($this->_controller->datamanager, $indexer, $this->_node[MIDCOM_NAV_OBJECT]);
                break;
            default:
                $_MIDCOM->generate_error
                (
                    MIDCOM_ERRCRIT,
                    "The DBA class {$this->_dbaclass} is unsupported"
                );
                // This will exit.
                break;
        }
    }

    /**
     * Determine the node that handles the DBA class and load the respective component
     */
    private function _load_component_node()
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $nap = new midcom_helper_nav();
        $component = $_MIDCOM->dbclassloader->get_component_for_class($this->_dbaclass);
        $_MIDCOM->componentloader->load($component);
        $topic_guid = $siteconfig->get_node_guid($component);
        $this->_node = $nap->resolve_guid($topic_guid);

        if (!$this->_node)
        {
            $_MIDCOM->generate_error
            (
                MIDCOM_ERRCRIT,
                "Could not load node information for topic {$topic_guid}. Last error was: " . midcom_connection::get_error_string()
            );
            // This will exit.
        }
    }

    /**
     * Helper that tries to determine the schemadb based on the requested DBA class
     *
     * @return string The path to the schemadb
     */
    private function _get_schemadb_snippet()
    {
        $config_key = 'schemadb';

        switch ($this->_dbaclass)
        {
            case 'org_openpsa_contacts_person_dba':
                $config_key .= '_person';
                break;
            default:
                $_MIDCOM->generate_error
                (
                    MIDCOM_ERRCRIT,
                    "The DBA class {$this->_dbaclass} is unsupported"
                );
                // This will exit.
                break;
        }

        return $this->_node[MIDCOM_NAV_CONFIGURATION]->get($config_key);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_create($handler_id, &$data)
    {
        switch ($this->_dbaclass)
        {
            case 'org_openpsa_contacts_person_dba':
                $title = 'person';
                break;
            default:
                $_MIDCOM->generate_error
                (
                    MIDCOM_ERRCRIT,
                    "The DBA class {$this->_dbaclass} is unsupported"
                );
                // This will exit.
                break;
        }
        $data['title'] = sprintf($this->_l10n_midcom->get('create %s'), $_MIDCOM->i18n->get_string($title, $this->_node[MIDCOM_NAV_COMPONENT]));

        midcom_show_style('popup_head');
        if ($this->_action != 'form')
        {
            if ($this->_object)
            {
                $data['jsdata'] = $this->object_to_jsdata();
            }
            midcom_show_style('chooser-create-after');
        }
        else
        {
            midcom_show_style('chooser-create');
        }
        midcom_show_style('popup_foot');
    }

    private function object_to_jsdata()
    {
        $jsdata = array
        (
            'id' => @$this->_object->id,
            'guid' => @$this->_object->guid,
            'pre_selected' => true
        );

        switch ($this->_dbaclass)
        {
            case 'org_openpsa_contacts_person_dba':
                //We need to reload the object so that name is constructed properly
                $this->_object = new org_openpsa_contacts_person_dba($this->_object->id);
                $jsdata['name'] = $this->_object->name;
                $jsdata['email'] = $this->_object->email;
                break;
            default:
                $_MIDCOM->generate_error
                (
                    MIDCOM_ERRCRIT,
                    "The DBA class {$this->_dbaclass} is unsupported"
                );
                // This will exit.
                break;
        }

        return json_encode($jsdata);
    }
}
?>