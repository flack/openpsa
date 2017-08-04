<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;

/**
 * Metadata editor.
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_metadata extends midcom_baseclasses_components_handler
{
    /**
     * Object requested for metadata editing
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    /**
     * Edit controller instance for Datamanager
     *
     * @var controller
     */
    private $_controller;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['object'] = $this->_object;
        $this->_request_data['controller'] = $this->_controller;
    }

    private function load_controller()
    {
        $schemadb = schemadb::from_path(midcom::get()->config->get('metadata_schema'));

        if (   $this->_config->get('enable_review_dates')
            && !$schemadb->get('metadata')->has_field('review_date')) {
            $fields = $schemadb->get('metadata')->get('fields');
            $fields['review_date'] = [
                'title' => $this->_l10n->get('review date'),
                'type' => 'date',
                'type_config' => [
                    'storage_type' => 'UNIXTIME',
                ],
                'storage' => [
                    'location' => 'parameter',
                    'domain' => 'midcom.helper.metadata',
                    'name' => 'review_date',
                ],
                'widget' => 'jsdate',
            ];
            $schemadb->get('metadata')->set('fields', $fields);
        }
        $dm = new datamanager($schemadb);
        return $dm
            ->set_storage($this->_object, 'metadata')
            ->get_controller();
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
        $this->_object->require_do('midgard:update');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        if (is_a($this->_object, 'midcom_db_topic')) {
            // This is a topic
            $this->_topic->require_do('midgard.admin.asgard:topic_management');
        }

        // Load the DM2 controller instance
        $this->_controller = $this->load_controller();
        switch ($this->_controller->process()) {
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
