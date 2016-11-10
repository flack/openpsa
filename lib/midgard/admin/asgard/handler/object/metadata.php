<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
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
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * Object requested for metadata editing
     *
     * @var midcom_core_dbaobject Object for metadata editing
     */
    private $_object = null;

    /**
     * Edit controller instance for Datamanager 2
     *
     * @var midcom_helper_datamanager2_controller
     */
    private $_controller = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['object'] = $this->_object;
        $this->_request_data['controller'] = $this->_controller;
    }

    public function get_schema_name()
    {
        return 'metadata';
    }

    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database(midcom::get()->config->get('metadata_schema'));

        if (   $this->_config->get('enable_review_dates')
            && !isset($schemadb['metadata']->fields['review_date'])) {
            $schemadb['metadata']->append_field(
                'review_date',
                array(
                    'title' => $this->_l10n->get('review date'),
                    'type' => 'date',
                    'type_config' => array(
                        'storage_type' => 'UNIXTIME',
                    ),
                    'storage' => array(
                        'location' => 'parameter',
                        'domain' => 'midcom.helper.metadata',
                        'name' => 'review_date',
                    ),
                    'widget' => 'jsdate',
                )
            );
        }
        return $schemadb;
    }

    /**
     * Handler for folder metadata. Checks for updating permissions, initializes
     * the metadata and the content topic itself. Handles also the sent form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);
        // FIXME: We should modify the schema according to whether or not scheduling is used
        $this->_object->require_do('midgard:update');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        if (is_a($this->_object, 'midcom_db_topic')) {
            // This is a topic
            $this->_topic->require_do('midgard.admin.asgard:topic_management');
        }

        // Load the DM2 controller instance
        $this->_controller = $this->get_controller('simple', $this->_object);
        switch ($this->_controller->process_form()) {
            case 'save':
                // Reindex the object
                //$indexer = midcom::get()->indexer;
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                midcom::get()->cache->invalidate($this->_object->guid);
                return new midcom_response_relocate("__mfa/asgard/object/metadata/{$this->_object->guid}");

            case 'cancel':
                return new midcom_response_relocate("__mfa/asgard/object/view/{$this->_object->guid}");
        }

        $this->_prepare_request_data();
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * Output the style element for metadata editing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_object_metadata');
    }
}
