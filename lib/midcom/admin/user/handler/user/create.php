<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: create.php 25318 2010-03-18 12:16:52Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Person creation class
 * 
 * @package midcom.admin.user
 */
class midcom_admin_user_handler_user_create extends midcom_baseclasses_components_handler
{
    var $_person = null;

    /**
     * Simple constructor
     * 
     * @access public
     */
    function __construct()
    {
        $this->_component = 'midcom.admin.user';
        parent::__construct();
     }

    function _on_initialize()
    {

        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $this->_l10n = $_MIDCOM->i18n->get_l10n('midcom.admin.user');
        $this->_request_data['l10n'] = $this->_l10n;

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css',
            )
        );

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'),$this->_request_data);

    }

    
    function _update_breadcrumb()
    {
        // Populate breadcrumb
        $tmp = Array();
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.user/",
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('midcom.admin.user', 'midcom.admin.user'),
        );
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.user/create/",
            MIDCOM_NAV_NAME => $this->_request_data['view_title'],
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    /**
     * Loads and prepares the schema database.
     */
    function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
    }

    /**
     * Internal helper, loads the controller for the current person. Any error triggers a 500.
     *
     * @access private
     */
    function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = 'default';
        $this->_controller->callback_object =& $this;
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to initialize a DM2 create controller.');
            // This will exit.
        }
    }

    /**
     * DM2 creation callback, creates a new person and binds it to the selected group.
     *
     * Assumes Admin Privileges.
     */
    function & dm2_create_callback (&$controller)
    {        
        // Create a new person
        $this->_person = new midcom_db_person();
        if (! $this->_person->create())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('We operated on this object:', $this->_person);
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Failed to create a new person, cannot continue. Last Midgard error was: '. midcom_connection::get_error_string());
            // This will exit.
        }

        return $this->_person;
    }
    
    /**
     * Handler method for listing style elements for the currently used component topic
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     * @return boolean Indicating successful request
     */
    function _handler_create($handler_id, $args, &$data)
    {
        $this->_load_controller();
        switch ($this->_controller->process_form())
        {
            case 'save':
                // Show confirmation for the user
                $_MIDCOM->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('person %s saved'), $this->_person->name));
                $_MIDCOM->relocate("__mfa/asgard_midcom.admin.user/edit/{$this->_person->guid}/");
                
            case 'cancel':
                $_MIDCOM->relocate('__mfa/asgard_midcom.admin.user/');
                // This will exit.
        }
        
        $data['view_title'] = $_MIDCOM->i18n->get_string('create user', 'midcom.admin.user');
        $_MIDCOM->set_pagetitle($data['view_title']);
        $this->_update_breadcrumb();
        
        return true;
    }
    
    /**
     * Show list of the style elements for the currently createed topic component
     * 
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    function _show_create($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();        
        $data['person'] =& $this->_person;
        $data['controller'] =& $this->_controller;
        midcom_show_style('midcom-admin-user-person-create');
        midgard_admin_asgard_plugin::asgard_footer();        
    }
}
?>