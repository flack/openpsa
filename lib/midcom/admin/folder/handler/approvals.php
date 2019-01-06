<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

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
     */
    public function _handler_approval(Request $request, $handler_id)
    {
        if (   !$request->request->has('guid')
            || !$request->request->has('return_to')) {
            throw new midcom_error('Cannot process approval request, request is incomplete.');
        }

        $object = midcom::get()->dbfactory->get_object_by_guid($request->request->get('guid'));
        $object->require_do('midcom:approve');

        if ($handler_id == 'approve') {
            $object->metadata->approve();
        } else {
            $object->metadata->unapprove();
        }

        return new midcom_response_relocate($request->request->get('return_to'));
    }
}
