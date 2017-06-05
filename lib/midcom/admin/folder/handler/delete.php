<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Handle the folder deleting requests
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_delete extends midcom_baseclasses_components_handler
{
    /**
     * Handler for folder deletion.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:delete');
        $this->_topic->require_do('midcom.admin.folder:topic_management');

        $nav = new midcom_helper_nav();
        $upper_node = $nav->get_node($nav->get_current_upper_node());

        $workflow = $this->get_workflow('delete', [
            'object' => $this->_topic,
            'recursive' => true,
            'success_url' => $upper_node[MIDCOM_NAV_ABSOLUTEURL]
        ]);
        return $workflow->run();
    }
}
