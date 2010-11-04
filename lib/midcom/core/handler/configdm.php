<?php
/**
 * @package midcom.core.handler
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: configdm.php 22991 2009-07-23 16:09:46Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Handler for Datamanager driven component configuration
 *
 * It defines a handler that can be used for Datamanager driven configuration. It requires
 * a few configuration directives: 'schemadb' must hold a valid path to a
 * schema database containing the actual configuration schema. The key 'schema'
 * may contain the name of the schema to use, it defaults to 'config' if omitted.
 * Finally, 'disable_return_to_topic', if set to true, will hide the automatically
 * added "Return to topic" toolbar item. This is useful for components which have only
 * a configuration interface but nothing more, it defaults to false.
 *
 * It uses the MidCOM l10n string 'close configuration screen' as title for the leaf-toolbar-item added
 * during the handle phase.
 *
 * The handler will load the datamanager library during the handle phase, just in case you
 * have not yet loaded it.
 *
 * <i>Important Note:</i> The schema you supply for configuration is always treated as having
 * a zero lock timeout, this parameter is enforced after initializing the datamanager. It is
 * important, as the config handler stays in the edit loop indefinitely.
 *
 * You may of course change both the request switch key and the URL to the handler.
 *
 * In addition, you should always add the corresponding command to the node toolbar during
 * the handle phase of your component.
 *
 * Full configuration example:
 *
 * <code>
 * <?php
 * $this->_request_switch['config'] = Array
 * (
 *     'handler' => Array ('midcom_core_handler_configdm', 'configdm'),
 *     'fixed_args' => Array('config'),
 *     'schemadb' => 'file:/net/nehmer/static/config/schemadb_config.inc',
 *     'schema' => 'config',
 * );
 *
 * // ...
 *
 * $this->_node_toolbar->add_item(Array(
 *     MIDCOM_TOOLBAR_URL => 'config/',
 *     MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
 *     MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
 *     MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
 *     MIDCOM_TOOLBAR_HIDDEN =>
 *     (
 *         ! $_MIDCOM->auth->can_do('midgard:update', $this->_topic)
 *      || ! $_MIDCOM->auth->can_do('midcom:component_config', $this->_topic)
 *     )
 * ));
 *
 * ?>
 * </code>
 *
 * <b>Extensibility</b>
 *
 * This class features a number of event handlers. They are designed to let you easily
 * enhance the configuration screen with your own code. Be aware though, that you need
 * to require_once this file before subclassing, as this handler is loaded on-demand
 * only.
 *
 * <b>Upgrading notes</b>
 *
 * Be aware that the handler name and all event handlers have been changed from "config_dm"
 * to "configdm" for uniformity reasons. If you move event handler code to a new subclass
 * of this handler, you need to rename the callbacks accordingly.
 *
 * @package midcom.core.handler
 */
class midcom_core_handler_configdm extends midcom_baseclasses_components_handler
{
    function __construct()
    {
        parent::__construct();
    }

    function _on_initialize()
    {
        if (!$_MIDCOM->componentloader->is_installed('midcom.helper.datamanager'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "midcom.helper.datamanager, used by configdm, is not installed.");
        }
        $_MIDCOM->load_library('midcom.helper.datamanager');
    }

    /**
     * Helper function that prepares a datamanager instance for the configdm handler.
     * You can override this function to influence the way the datamanager is initialized,
     * for example to modify the schema after it has been loaded.
     *
     * Normally, you should always call the base class implementation, and modify the
     * datamanager in $data['datamanager'] afterwards.
     *
     * @access protected
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data (note the reference when inheriting).
     */
    function _handler_configdm_prepare($handler_id, &$data)
    {
        // Load the datamanager, then create an instance.
        $data['datamanager'] = new midcom_helper_datamanager($this->_master->_handler['schemadb']);

        if ($data['datamanager'] == false)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to instantiate configuration datamanager.');
            // This will exit.
        }

        // Call the dm prepared event handler.
        $this->_on_handler_configdm_prepared($data['datamanager']);

        if (! $data['datamanager']->init($this->_topic, $this->_master->_handler['schema']))
        {
            debug_add('Failed to initialize the datamanager.', MIDCOM_LOG_CRIT);
            debug_print_r('Topic object we tried was:', $this->_config_topic);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to initialize configuration datamanager.');
            // This will exit.
        }

        // Turn off locking.
        $data['datamanager']->_layoutdb[$this->_master->_handler['schema']]['locktimeout'] = 0;
    }

    /**
     * Event handler, called after the configuration datamanager instance has been created but not yet
     * initialized. Use this hook to modify the schema where necessary.
     *
     * @param midcom_helper_datamanager $datamanager A reference(!) to the datamanager handling the request.
     * @access protected
     */
    function _on_handler_configdm_prepared(&$datamanager) { }

    /**
     * Datamanager configuration handler interface.
     *
     * Displays the back to index toolbar item unless surpressed by the configuration and
     * processes the DM form data, staying in the edit loop.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param mixed &$data The local request data.
     * @return boolean Indicating success.
     * @access protected
     */
    function _handler_configdm($handler_id, $args, &$data)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Deprecated midcom.helper.datamanager-based configdm used in {$this->_topic->component}.", MIDCOM_LOG_WARN);
        debug_pop();

        // Auto-Complete the config
        if (! array_key_exists('schema', $this->_master->_handler))
        {
            $this->_master->_handler['schema'] = 'config';
        }
        if (! array_key_exists('disable_return_to_topic', $this->_master->_handler))
        {
            $this->_master->_handler['disable_return_to_topic'] = false;
        }

        // Verify permissions
        $_MIDCOM->auth->require_do('midgard:update', $this->_topic);
        $_MIDCOM->auth->require_do('midcom:component_config', $this->_topic);

        // Call the pre-preparation event handler.
        $this->_on_handler_configdm_preparing();

        $this->_handler_configdm_prepare($handler_id, $data);

        // Add the toolbar items, if necessary
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => '',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('close configuration screen'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
                MIDCOM_TOOLBAR_ENABLED => true
            )
        );

        switch ($data['datamanager']->process_form()) {
            case MIDCOM_DATAMGR_SAVED:
                // Call the event handler
                $this->_on_handler_configdm_saved();
                break;

            case MIDCOM_DATAMGR_EDITING:
            case MIDCOM_DATAMGR_CANCELLED:
                // Do nothing here, the datamanager will invalidate the cache.
                // Apart from that, let the user edit the configuration as long
                // as he likes.
                break;

            case MIDCOM_DATAMGR_FAILED:
                $this->errstr = "Datamanager: " . $GLOBALS["midcom_errstr"];
                $this->errcode = MIDCOM_ERRCRIT;
                debug_pop();
                return false;
        }

        $_MIDCOM->add_link_head(
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL."/midcom.helper.datamanager/datamanager.css",
            )
        );

        // Add the view to breadcrumb trail
        $tmp = Array();
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => 'config/',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('component configuration', 'midcom'),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        // Hide the button in toolbar
        // TODO: Figure out the real URL of the config editor
        // $this->_node_toolbar->hide_item('config/');

        // Set page title
        $data['topic'] = $this->_topic;
        $data['component'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        $data['title'] = sprintf($_MIDCOM->i18n->get_string('component %s configuration for folder %s', 'midcom'), $_MIDCOM->i18n->get_string($data['component'], $data['component']), $data['topic']->extra);
        $_MIDCOM->set_pagetitle($data['title']);

        return true;
    }

    /**
     * Event handler, called before the configuration datamanager is created. Use this to
     * prepare anything that is required to start up the Datamanager.
     *
     * @access protected
     */
    function _on_handler_configdm_preparing() { }

    /**
     * Event handler, called when the configuration system has successfully stored
     * new configuration settings.
     *
     * @access protected
     */
    function _on_handler_configdm_saved() { }


    /**
     * Simple display handler for the configdm handler, it uses the MidCOM L10n string
     * 'component configuration' as heading, and immediately displays the form afterwards.
     *
     * If you need any styling, you should override this.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     * @access protected
     */
    function _show_configdm($handler_id, &$data)
    {
        echo "<h1>{$data['title']}</h1>\n";
        $data['datamanager']->display_form();
    }

}

?>