<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Handle the requests for approving objects.
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_approvals extends midcom_baseclasses_components_handler
{
    /**
     * Checks the integrity of the content topic and gets the stored approvals of
     * the content folder.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_approval($handler_id, array $args, array &$data)
    {
        if (   !array_key_exists('guid', $_REQUEST)
            || !array_key_exists('return_to', $_REQUEST)) {
            throw new midcom_error('Cannot process approval request, request is incomplete.');
        }

        $object = midcom::get()->dbfactory->get_object_by_guid($_REQUEST['guid']);
        $object->require_do('midcom:approve');

        if ($handler_id == '____ais-folder-approve') {
            $object->metadata->approve();
        } else {
            $object->metadata->unapprove();
        }

        return new midcom_response_relocate($_REQUEST['return_to']);
    }
}
