<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Metadata editor.
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_metadata extends midcom_baseclasses_components_handler
{
    private midcom_core_dbaobject $object;

    /**
     * Handler for folder metadata. Checks for updating permissions, initializes
     * the metadata and the content topic itself. Handles also the sent form.
     */
    public function _handler_metadata(Request $request, string $guid)
    {
        $this->object = midcom::get()->dbfactory->get_object_by_guid($guid);
        $this->object->require_do('midgard:update');

        if ($this->object instanceof midcom_db_topic) {
            $this->object->require_do('midcom.admin.folder:topic_management');
        }

        $object_label = midcom_helper_reflector::get($this->object)->get_object_label($this->object);
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('edit metadata of %s'), $object_label));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->object->metadata->get_datamanager()->get_controller(),
            'save_callback' => $this->save_callback(...)
        ]);
        return $workflow->run($request);
    }

    public function save_callback()
    {
        midcom::get()->cache->invalidate($this->object->guid);
    }
}
