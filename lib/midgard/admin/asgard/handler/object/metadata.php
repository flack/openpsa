<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: metadata.php 25321 2010-03-18 15:18:49Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Metadata editor.
 *
 * This handler uses midcom.helper.datamanager2 to edit object metadata properties
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_metadata extends midcom_baseclasses_components_handler
{
    /**
     * Object requested for metadata editing
     *
     * @access private
     * @var mixed Object for metadata editing
     */
    var $_object = null;

    /**
     * Edit controller instance for Datamanager 2
     *
     * @access private
     * @var midcom_helper_datamanager2_controller
     */
    var $_controller = null;

    /**
     * Datamanager 2 schema instance
     *
     * @access private
     * @var midcom_helper_datamanager2_schema
     */
    var $_schemadb = null;

    /**
     * Constructor, call for the class parent constructor method.
     */
    function __construct()
    {
        $this->_component = 'midgard.admin.asgard';
    }

    function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;

        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $this->_request_data['object'] =& $this->_object;
        $this->_request_data['controller'] =& $this->_controller;
        $this->_request_data['schemadb'] =& $this->_schemadb;
    }

    /**
     * Load the DM2 edit controller instance
     *
     * @access private
     * @return boolean Indicating success of DM2 edit controller instance
     */
    function _load_controller()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($GLOBALS['midcom_config']['metadata_schema']);

        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;

        if (   $this->_config->get('enable_review_dates')
            && !isset($this->_schemadb['metadata']->fields['review_date']))
        {
            $this->_schemadb['metadata']->append_field
            (
                'review_date',
                array
                (
                    'title' => $this->_l10n->get('review date'),
                    'type' => 'date',
                    'type_config' => array
                    (
                        'storage_type' => 'UNIXTIME',
                    ),
                    'storage' => array
                    (
                        'location' => 'parameter',
                        'domain' => 'midcom.helper.metadata',
                        'name' => 'review_date',
                    ),
                    'widget' => 'jsdate',
                )
            );
        }

        $this->_controller->set_storage($this->_object, 'metadata');

        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
            // This will exit.
        }
    }

    /**
     * Handler for folder metadata. Checks for updating permissions, initializes
     * the metadata and the content topic itself. Handles also the sent form.
     *
     * @access private
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
        if (   !$this->_object
            || !$this->_object->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The GUID '{$args[0]}' was not found.");
            // This will exit.
        }

        // FIXME: We should modify the schema according to whether or not scheduling is used
        $this->_object->require_do('midgard:update');
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        if (is_a($this->_object, 'midcom_db_topic'))
        {
            // This is a topic
            $this->_topic->require_do('midgard.admin.asgard:topic_management');
        }

        $this->_metadata = midcom_helper_metadata::retrieve($this->_object);

        if (! $this->_metadata)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to retrieve Metadata for " . get_class($this->_object) . " {$this->_object->guid}.");
            // This will exit.
        }

        // Load the DM2 controller instance
        $this->_load_controller();
        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the object
                //$indexer = $_MIDCOM->get_service('indexer');
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                // *** FALL-THROUGH ***
                $_MIDCOM->cache->invalidate($this->_object->guid);
                $_MIDCOM->relocate("__mfa/asgard/object/metadata/{$this->_object->guid}");
                // This will exit.

            case 'cancel':
                $_MIDCOM->relocate("__mfa/asgard/object/view/{$this->_object->guid}");
                // This will exit.
        }

        $this->_prepare_request_data();
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return true;
    }

    /**
     * Output the style element for metadata editing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     * @access private
     */
    function _show_edit($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();
        midcom_show_style('midgard_admin_asgard_object_metadata');
        midgard_admin_asgard_plugin::asgard_footer();
    }
}
?>
