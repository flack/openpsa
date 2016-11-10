<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\workflow\delete;

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
        // Symlink support requires that we use actual URL topic object here
        $urltopics = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_URLTOPICS);
        if ($urltopic = end($urltopics)) {
            $this->_topic = $urltopic;
        }

        $this->_topic->require_do('midgard:delete');
        $this->_topic->require_do('midcom.admin.folder:topic_management');

        $nav = new midcom_helper_nav();
        $upper_node = $nav->get_node($nav->get_current_upper_node());

        $workflow = $this->get_workflow('delete', array
        (
            'object' => $this->_topic,
            'recursive' => true,
            'success_url' => $upper_node[MIDCOM_NAV_ABSOLUTEURL]
        ));
        if ($workflow->get_state() === delete::CONFIRMED) {
            $this->check_symlinks();
        }
        return $workflow->run();
    }

    /**
     * Deletes the folder and _midcom_db_article_ objects stored in it.
     */
    private function check_symlinks()
    {
        if (midcom::get()->config->get('symlinks')) {
            midcom::get()->auth->request_sudo('midcom.admin.folder');
            $qb_topic = midcom_db_topic::new_query_builder();
            $qb_topic->add_constraint('symlink', '=', $this->_topic->id);
            $symlinks = $qb_topic->execute();
            if (!empty($symlinks)) {
                $msg = 'Refusing to delete Folder because it has symlinks:';
                $nap = new midcom_helper_nav();
                foreach ($symlinks as $symlink) {
                    $node = $nap->get_node($symlink->id);
                    $msg .= ' ' . $node[MIDCOM_NAV_FULLURL];
                }

                throw new midcom_error($msg);
            }
            midcom::get()->auth->drop_sudo();
        }
    }
}
