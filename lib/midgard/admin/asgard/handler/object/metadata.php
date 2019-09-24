<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Metadata editor.
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_metadata extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    /**
     * Object requested for metadata editing
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    /**
     * Edit controller instance for Datamanager
     *
     * @var midcom\datamanager\controller
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

    /**
     * Handler for folder metadata. Checks for updating permissions, initializes
     * the metadata and the content topic itself. Handles also the sent form.
     */
    public function _handler_edit(Request $request, string $handler_id, string $guid, array &$data)
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($guid);
        $this->_object->require_do('midgard:update');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        if ($this->_object instanceof midcom_db_topic) {
            // This is a topic
            $this->_topic->require_do('midgard.admin.asgard:topic_management');
        }

        // Load the controller instance
        $this->_controller = datamanager::from_schemadb(midcom::get()->config->get('metadata_schema'))
            ->set_storage($this->_object, 'metadata')
            ->get_controller();
        switch ($this->_controller->handle($request)) {
            case 'save':
                // Reindex the object
                //$indexer = midcom::get()->indexer;
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                midcom::get()->cache->invalidate($this->_object->guid);
                return new midcom_response_relocate($this->router->generate('object_metadata', ['guid' => $this->_object->guid]));

            case 'cancel':
                return new midcom_response_relocate($this->router->generate('object_view', ['guid' => $this->_object->guid]));
        }

        $this->_prepare_request_data();
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return $this->get_response('midgard_admin_asgard_object_metadata');
    }
}
