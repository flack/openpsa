<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: preferences.php 23025 2009-07-28 10:03:50Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Preferences interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_preferences extends midcom_baseclasses_components_handler
{
    /**
     * Controller instance
     *
     * @access private
     * @var midcom_helper_datamanager2_controller
     */
    var $_controller;

    /**
     * Schemadb instance
     *
     * @access private
     * @var midcom_helper_datamanager2_schema
     */
    var $_schemadb;

    /**
     * User for the preferences page
     *
     * @access private
     * @var midcom_db_person
     */
    var $_person;

    /**
     * Status of the request
     *
     * @access private
     * @var boolean
     */
    var $_status = true;

    /**
     * Connect to the parent class constructor
     *
     * @access public
     */
    function __construct()
    {
        $this->_component = 'midgard.admin.asgard';
        parent::__construct();
    }

    /**
     * Startup routines
     *
     * @access public
     */
    function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;

        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    /**
     * Load the controller instance
     *
     * @access private
     */
    function _load_controller()
    {
        // Get the user preferences schema
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_preferences'));

        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb = $this->_schemadb;
        $this->_controller->set_storage($this->_person);

        if (!$this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to generate the edit controller');
            // This will exit
        }
    }

    /**
     * Process the UI information
     *
     * @access private
     */
    function _process_request_data(&$data)
    {
        $data['view_title'] = $_MIDCOM->i18n->get_string('user preferences', 'midgard.admin.asgard');
        $data['asgard_toolbar'] = new midcom_helper_toolbar();
        $data['controller'] =& $this->_controller;

        midgard_admin_asgard_plugin::get_common_toolbar($data);

        // Set the breadcrumb data
        $tmp = array();
        $tmp[] = array
        (
            MIDCOM_NAV_URL => '__mfa/asgard/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => '__mfa/asgard/preferences/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('user preferences', 'midgard.admin.asgard'),
        );

        if ($this->_person->guid !== $_MIDCOM->auth->user->guid)
        {
            $tmp[] = array
            (
                MIDCOM_NAV_URL => "__mfa/asgard/preferences/{$this->_person->guid}/",
                MIDCOM_NAV_NAME => $this->_person->name,
            );
        }

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

    }

    /**
     * Handle the preference request
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_preferences($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        if (isset($args[0]))
        {
            $this->_person = new midcom_db_person($args[0]);
        }
        else
        {
            $this->_person = new midcom_db_person($_MIDGARD['user']);
        }

        // Bulletproofing the person
        if (   !$this->_person
            || !isset($this->_person->guid)
            || !$this->_person->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to get the requested person');
            // This will exit
        }

        // Load the controller instance
        $this->_load_controller();

        $return_page = '__mfa/asgard/';
        if(isset($_GET['return_uri']))
        {
            $return_page = $_GET['return_uri'];
        }
        // Process the requested form
        switch ($this->_controller->process_form())
        {
            case 'save':
                $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'), $_MIDCOM->i18n->get_string('preferences saved', 'midgard.admin.asgard'));
                $_MIDCOM->relocate($return_page);
                // This will exit
                break;
            case 'cancel':
                $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'), $_MIDCOM->i18n->get_string('cancelled', 'midcom'));
                $_MIDCOM->relocate($return_page);
                // This will exit
                break;
        }


        // Load the common data
        $this->_process_request_data($data);

        return true;
    }

    /**
     * Show the preferences page
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_preferences($handler_id, &$data)
    {
        if (isset($_GET['ajax']))
        {
            midcom_show_style('midgard_admin_asgard_preferences');
            return true;
        }
        midcom_show_style('midgard_admin_asgard_header');
        midcom_show_style('midgard_admin_asgard_middle');
        midcom_show_style('midgard_admin_asgard_preferences');
        midcom_show_style('midgard_admin_asgard_footer');
    }

    /**
     * Static method for getting the languages, but
     *
     * @access public
     * @static
     */
    function get_languages()
    {
        $lang_str = $_MIDCOM->i18n->get_current_language();
        $languages = $_MIDCOM->i18n->list_languages();

        if (!array_key_exists($lang_str, $languages))
        {
            return $languages;
        }

        // Initialize a new array for the current language
        $language = array();
        $language[$lang_str] = $languages[$lang_str];

        // Remove the reference from the original array
        unset($languages[$lang_str]);

        // Join the arrays
        return array_merge($language, $languages);
    }

    /**
     * AJAX backend for saving data on the fly
     *
     * @access public
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_ajax($handler_id, $args, &$data)
    {
        $this->_person = new midcom_db_person($_MIDGARD['user']);

        // Check for the ACL's
        if (!$this->_person->can_do('midgard:update'))
        {
            return false;
        }

        // Patch for Midgard ACL problem of setting person's own parameters
        $_MIDCOM->auth->request_sudo('midgard.admin.asgard');

        debug_push_class(__CLASS__, __FUNCTION__);

        foreach ($_POST as $key => $value)
        {
             if (is_array($value))
             {
                 $value = serialize($value);
             }

             if (!$this->_person->set_parameter('midgard.admin.asgard:preferences', $key, $value))
             {
                 $this->_status = false;
                 $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'), sprintf($_MIDCOM->i18n->get_string('failed to save the preference for %s', 'midgard.admin.asgard'), $_MIDCOM->i18n->get_string($key, 'midgard.admin.asgard')));
             }

             debug_add("Added configuration key-value pair {$key} => {$value}", MIDCOM_LOG_DEBUG);
        }

        debug_pop();

        $_MIDCOM->auth->drop_sudo();

        return true;
    }

    /**
     * Possible user output besides the UI message that was set
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_ajax($handler_id, &$data)
    {
        // Do nothing at the moment
    }
}
?>