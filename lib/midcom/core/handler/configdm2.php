<?php
/**
 * @package midcom.core.handler
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: configdm2.php 25318 2010-03-18 12:16:52Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * midcom.helper.datamanager2 based configuration
 *
 * Usage:
 *
 * 1. Write a midcom_helper_datamanager2_schema compatible configuration
 *    schema and place it among your component files
 * 2. Point a configuration key 'schemadb_config' to it within your
 *    component configuration (_config/config.inc_)
 * 3. Refer to DM2 component configuration helper with a request handler,
 *    e.g.
 *
 * <code>
 *     $this->_request_handler['config'] = array
 *     (
 *         'handler' => array ('midcom_core_handler_configdm2', 'config'),
 *         'fixed_args' => array ('config'),
 *     );
 * </code>
 *
 * @package midcom.core.handler
 */
class midcom_core_handler_configdm2 extends midcom_baseclasses_components_handler
{
    /**
     * DM2 controller instance
     *
     * @access private
     * @var midcom_helper_datamanager2_controller $_controller
     */
    var $_controller;

    /**
     * DM2 configuration schema
     *
     * @access private
     * @var midcom_helper_datamanager2_schema $_schemadb
     */
    var $_schemadb;

    /**
     * Load midcom.helper.datamanager2. Called on handler initialization phase.
     *
     * @access public
     */
    function _on_initialize()
    {
        $_MIDCOM->componentloader->load('midcom.helper.datamanager2');
    }

    /**
     * Load midcom_helper_datamanager2_controller instance or output an error on any error
     *
     * @access private
     * @return boolean Indicating success
     */
    function _load_controller()
    {
        if (isset($this->_master->handler['schemadb']))
        {
            $this->_schemadb_path = $this->_master->handler['schemadb'];
        }
        elseif ($this->_config->get('schemadb_config'))
        {
            $this->_schemadb_path = $this->_config->get('schemadb_config');
        }
        else
        {
            debug_add(__CLASS__, __FUNCTION__);
            debug_add('No configuration schema defined', MIDCOM_LOG_ERROR);

            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "No configuration schema defined");
            // This will exit
        }

        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_schemadb_path);

        if (empty($this->_schemadb))
        {
            debug_add(__CLASS__, __FUNCTION__);
            debug_add('Failed to load the schemadb', MIDCOM_LOG_ERROR);

            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to load configuration schemadb');
            // This will exit
        }

        // Create a 'simple' controller
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_topic);

        if (! $this->_controller->initialize())
        {
            debug_add(__CLASS__, __FUNCTION__);
            debug_add('Failed to initialize the configuration controller');

            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for photo {$this->_photo->id}.");
            // This will exit.
        }

        return true;
    }

    /**
     * Generic handler for all the DM2 based configuration requests
     *
     * @access public
     * @param string $handler_id    Name of the handler
     * @param array  $args          Variable arguments
     * @param array  $data          Miscellaneous output data
     * @return boolean              Indicating success
     */
    function _handler_config($handler_id, $args, &$data)
    {
        // Require corresponding ACL's
        $this->_topic->require_do('midgard:update');
        $this->_topic->require_do('midcom:component_config');

        // Add DM2 link head
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/legacy.css');

        if (   method_exists($this, '_load_datamanagers')
            && method_exists($this, '_load_objects'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'config/recreate/',
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('recreate images', 'midcom'),
                    // TODO: better icon
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/recurring.png',
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                    (
                        'midcom_core_handler_configdm2_recreateok' => true,
                    )
                )
            );
        }

        // Load the midcom_helper_datamanager2_controller for form processing
        $this->_load_controller();

        // Process the form
        switch ($this->_controller->process_form())
        {
            case 'save':
                $_MIDCOM->uimessages->add($this->_l10n_midcom->get('component configuration'), $this->_l10n_midcom->get('configuration saved'));
                $_MIDCOM->relocate('');
                // This will exit
                break;

            case 'cancel':
                $_MIDCOM->uimessages->add($this->_l10n_midcom->get('component configuration'), $this->_l10n_midcom->get('cancelled'));
                $_MIDCOM->relocate('');
                // This will exit
                break;
        }

        // Update the breadcrumb and page title
        $this->add_breadcrumb('config/', $this->_l10n_midcom->get('component configuration'));

        $data['component'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        $data['title'] = sprintf($_MIDCOM->i18n->get_string('component %s configuration for folder %s', 'midcom'), $_MIDCOM->i18n->get_string($data['component'], $data['component']), $data['topic']->extra);
        $_MIDCOM->set_pagetitle($data['title']);

        return true;
    }

    /**
     * Show the configuration screen
     *
     * @access public
     * @param string $handler_id    Name of the handler
     * @param array  $data          Miscellaneous output data
     */
    function _show_config($handler_id, &$data)
    {
        if (   function_exists('mgd_is_element_loaded')
            && mgd_is_element_loaded('dm2_config'))
        {
            $data['controller'] =& $this->_controller;
            midcom_show_element('dm2_config');
            return;
        }

        // No user-defined element, show directly here
        echo "<div class=\"dm2_config\">\n";
        echo "<h1>{$data['title']}</h1>\n";
        $this->_controller->display_form();
        echo "</div>\n";
    }

    /**
     * Handler for regenerating all derived images used in the folder.
     *
     * If used in a component, you should implement the _load_datamanagers and _load_objects methods in an
     * inherited handler class.
     *
     * _load_datamanagers must return an array of midcom_helper_datamanager2_datamanager objects indexed by
     * DBA class name.
     *
     * _load_objects must return an array of DBA objects.
     *
     * @access public
     * @param string $handler_id    Name of the handler
     * @param array  $args          Variable arguments
     * @param array  $data          Miscellaneous output data
     * @return boolean              Indicating success
     */
    function _handler_recreate($handler_id, $args, &$data)
    {
        if (!method_exists($this, '_load_datamanagers'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, '_load_datamanagers method not available, recreation support disabled.');
            // This will exit.
        }

        if (!method_exists($this, '_load_objects'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, '_load_objects method not available, recreation support disabled.');
            // This will exit.
        }

        // Require corresponding ACL's
        $this->_topic->require_do('midgard:update');
        $this->_topic->require_do('midcom:component_config');

        if (array_key_exists('midcom_core_handler_configdm2_recreatecancel', $_POST))
        {
            $_MIDCOM->relocate('config/');
            // This will exit.
        }

        if (!array_key_exists('midcom_core_handler_configdm2_recreateok', $_POST))
        {
            $_MIDCOM->relocate('config/');
            // This will exit.
        }

        $data['datamanagers'] = $this->_load_datamanagers();

        // Update the breadcrumb and page title
        $this->add_breadcrumb('config/', $this->_l10n_midcom->get('component configuration'));
        $this->add_breadcrumb('config/recreate/', $this->_l10n_midcom->get('recreate images'));
        $data['component'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        $data['title'] = sprintf($_MIDCOM->i18n->get_string('recreate images for folder %s', 'midcom'), $data['topic']->extra);
        $_MIDCOM->set_pagetitle($data['title']);

        return true;
    }

    /**
     * Show the recreation screen
     *
     * @access public
     * @param string $handler_id    Name of the handler
     * @param array  $data          Miscellaneous output data
     */
    function _show_recreate($handler_id, &$data)
    {
        //Disable limits
        // TODO: Could this be done more safely somehow
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);

        if (   function_exists('mgd_is_element_loaded')
            && mgd_is_element_loaded('dm2_config_recreate'))
        {
            midcom_show_element('dm2_config_recreate');
            return;
        }

        // No user-defined element, show directly here

        echo "<h1>{$data['title']}</h1>\n";

        echo "<p>" . $_MIDCOM->i18n->get_string('recreating', 'midcom') . "</p>\n";

        echo "<pre>\n";
        $objects = $this->_load_objects();
        foreach ($objects as $object)
        {
            $type = get_class($object);
            if (!isset($data['datamanagers'][$type]))
            {
                echo sprintf($_MIDCOM->i18n->get_string('not recreating object %s %s, reason %s', 'midcom'), $type, $object->guid, 'No datamanager defined') . "\n";
                continue;
            }

            if (   !$object->can_do('midgard:update')
                || !$object->can_do('midgard:attachments'))
            {
                echo sprintf($_MIDCOM->i18n->get_string('not recreating object %s %s, reason %s', 'midcom'), $type, $object->guid, 'Insufficient privileges') . "\n";
                continue;
            }

            echo sprintf($_MIDCOM->i18n->get_string('recreating object %s %s', 'midcom'), $type, $object->guid) . ': ';
            $data['datamanagers'][$type]->autoset_storage($object);
            if (!$data['datamanagers'][$type]->recreate())
            {
                echo "SKIPPED\n";
            }
            else
            {
                echo "OK\n";
            }
        }
        echo "</pre>\n";

        echo "<p>" . $_MIDCOM->i18n->get_string('done', 'midcom') . "</p>\n";
    }
}
?>