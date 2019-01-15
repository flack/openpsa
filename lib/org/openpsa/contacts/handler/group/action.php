<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_group_action extends midcom_baseclasses_components_handler
{
    /**
     * @param Request $request The request object
     * @return midcom_response_json
     */
    public function _handler_update_member_title(Request $request)
    {
        $response = new midcom_response_json;
        $response->status = false;

        if ($request->request->has('guid') && $request->request->has('title')) {
            try {
                $member = new midcom_db_member($request->request->get('guid'));
                $member->require_do('midgard:update');
                $member->extra = $request->request->get('title');
                $response->status = $member->update();
            } catch (midcom_error $e) {
                $e->log();
            }
            $response->message = midcom_connection::get_error_string();
        }

        return $response;
    }

    /**
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_members($guid, array &$data)
    {
        $data['group'] = new org_openpsa_contacts_group_dba($guid);
        $qb = new org_openpsa_qbpager(midcom_db_member::class, 'group_members');
        $qb->add_constraint('gid', '=', $data['group']->id);
        $qb->results_per_page = 10;
        $data['members_qb'] = $qb;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_members($handler_id, array &$data)
    {
        $results = $data['members_qb']->execute();
        if (!empty($results)) {
            midcom_show_style('show-group-persons-header');
            foreach ($results as $member) {
                $this->_request_data['member'] = $member;
                $this->_request_data['member_title'] = $member->extra;

                $this->_request_data['person'] = new org_openpsa_contacts_person_dba($member->uid);
                midcom_show_style('show-group-persons-item');
            }
            midcom_show_style('show-group-persons-footer');
        }
    }

    /**
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_subgroups($guid, array &$data)
    {
        $group = new org_openpsa_contacts_group_dba($guid);
        $qb = org_openpsa_contacts_group_dba::new_query_builder();
        $qb->add_constraint('owner', '=', $group->id);
        $data['results'] = $qb->execute();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_subgroups($handler_id, array &$data)
    {
        if (!empty($data['results'])) {
            midcom_show_style('show-group-subgroups-header');
            foreach ($data['results'] as $subgroup) {
                $this->_request_data['subgroup'] = $subgroup;
                midcom_show_style('show-group-subgroups-item');
            }
            midcom_show_style('show-group-subgroups-footer');
        }
    }
}
