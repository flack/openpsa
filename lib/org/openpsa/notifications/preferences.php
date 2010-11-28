<?php
/**
 * @package org.openpsa.notifications
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: preferences.php 25318 2010-03-18 12:16:52Z indeyets $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.notifications preference handler class
 *
 * @package org.openpsa.notifications
 */
class org_openpsa_notifications_preferences extends midcom_baseclasses_components_handler
{
    var $schemadb = array();

    /**
     * Simple constructor, which only initializes the parent constructor.
     */
    function __construct()
    {
        $this->_component = 'org.openpsa.notifications';
    }

    /**
     * Get the plugin handlers, which act alike with Request Switches of MidCOM
     * Baseclasses Components (midcom.baseclasses.components.request)
     */
    public function get_plugin_handlers()
    {
        return array
        (
            'edit' => array
            (
                'handler' => array('org_openpsa_notifications_preferences', 'edit'),
                'fixed_args' => array('edit'),
            ),
        );
    }

    function _list_notifiers()
    {
        // TODO: Figure out which notifiers are possible
        $notifiers = array
        (
            ''         => 'default',
            'none'     => 'none',
            'email'    => 'email',
        );

        if (   $this->_config->get('nabaztag_serial_number')
            && $this->_config->get('nabaztag_api_token'))
        {
            $notifiers['nabaztag'] = 'nabaztag';
        }

        return $notifiers;
    }

    function _populate_schema()
    {
        $notifiers = $this->_list_notifiers();

        // Load actions of various components
        $customdata = $_MIDCOM->componentloader->get_all_manifest_customdata('org.openpsa.notifications');
        foreach ($customdata as $component => $actions)
        {
            $prepended = false;

            foreach ($actions as $action => $settings)
            {
                $prepend = '';
                if (!$prepended)
                {
                    $prepend = "<h3 style='clear: left;'>" . $_MIDCOM->i18n->get_string($component, $component) . "</h3>\n";
                    $prepended = true;
                }

                $action_key = "{$component}:{$action}";
                $this->_schemadb['notifications']->append_field
                (
                    str_replace(':', '_', str_replace('.', '_', $action_key)),
                    array
                    (
                        'title'   => $_MIDCOM->i18n->get_string("action {$action}", $component),
                        'storage' => array
                        (
                            'location' => 'configuration',
                            'domain'   => 'org.openpsa.notifications',
                            'name'     => $action_key,
                        ),
                        'type'    => 'select',
                        'widget'  => 'select',
                        'type_config' => array
                        (
                            'options' => $notifiers,
                        ),
                        'static_prepend' => $prepend,
                    )
                );
            }
        }
    }

    function _on_initialize()
    {
        parent::_on_initialize();

        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $this->_populate_schema();
    }

    /**
     * Internal helper, loads the controller for the current task. Any error triggers a 500.
     *
     * @access private
     */
    function _load_controller()
    {
        $user = $_MIDCOM->auth->user->get_storage();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($user, 'notifications');
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for task {$user->id}.");
            // This will exit.
        }
    }

    /**
     * Handles the notification preferences edit form
     *
     * @access private
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $_MIDCOM->relocate("");

            case 'cancel':
                // This will exit.
        }

        return true;
    }

    /**
     * Displays the notification preferences edit form
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     * @access private
     */
    function _show_edit($handler_id, &$data)
    {
        echo "<h1>" . $this->_l10n->get('notification preferences') . "</h1>\n";
        $this->_controller->display_form();
    }
}
?>