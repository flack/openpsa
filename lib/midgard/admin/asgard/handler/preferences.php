<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Preferences interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_preferences extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * User for the preferences page
     *
     * @var midcom_db_person
     */
    private $_person;

    /**
     * Status of the request
     *
     * @var boolean
     */
    private $_status = true;

    /**
     * Startup routines
     */
    public function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;

        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    /**
     * Get the user preferences schema
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_preferences'));
    }

    /**
     * Process the UI information
     */
    private function _process_request_data(&$data)
    {
        $data['view_title'] = $_MIDCOM->i18n->get_string('user preferences', 'midgard.admin.asgard');
        $data['asgard_toolbar'] = new midcom_helper_toolbar();

        midgard_admin_asgard_plugin::get_common_toolbar($data);

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb('__mfa/asgard/preferences/', $_MIDCOM->i18n->get_string('user preferences', 'midgard.admin.asgard'));

        if ($this->_person->guid !== $_MIDCOM->auth->user->guid)
        {
            $this->add_breadcrumb("__mfa/asgard/preferences/{$this->_person->guid}/", $this->_person->name);
        }
    }

    /**
     * Handle the preference request
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_preferences($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        if (isset($args[0]))
        {
            $this->_person = new midcom_db_person($args[0]);
        }
        else
        {
            $this->_person = new midcom_db_person(midcom_connection::get_user());
        }

        // Bulletproofing the person
        if (!$this->_person->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to get the requested person');
            // This will exit
        }

        // Load the controller instance
        $data['controller'] = $this->get_controller('simple', $this->_person);

        $return_page = '__mfa/asgard/';
        if(isset($_GET['return_uri']))
        {
            $return_page = $_GET['return_uri'];
        }
        // Process the requested form
        switch ($data['controller']->process_form())
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
    public function _show_preferences($handler_id, &$data)
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
     * @static
     */
    public function get_languages()
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
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_ajax($handler_id, $args, &$data)
    {
        $this->_person = new midcom_db_person(midcom_connection::get_user());

        // Check for the ACL's
        if (!$this->_person->can_do('midgard:update'))
        {
            return false;
        }

        // Patch for Midgard ACL problem of setting person's own parameters
        $_MIDCOM->auth->request_sudo('midgard.admin.asgard');


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

        $_MIDCOM->auth->drop_sudo();

        return true;
    }

    /**
     * Possible user output besides the UI message that was set
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_ajax($handler_id, &$data)
    {
        // Do nothing at the moment
    }
}
?>