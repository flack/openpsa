<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_group_action extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_update_member_title($handler_id, array $args, array &$data)
    {
        $response = new midcom_response_json;
        $response->status = false;

        if (   !empty($_POST['guid'])
            && array_key_exists('title', $_POST))
        {
            try
            {
                $member = new midcom_db_member($_POST['guid']);
                $member->require_do('midgard:update');
                $member->extra = $_POST['title'];
                $response->status = $member->update();
            }
            catch (midcom_error $e)
            {
                $e->log();
            }
            $response->message = midcom_connection::get_error_string();
        }

        return $response;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_members($handler_id, array $args, array &$data)
    {
        $data['group'] = new org_openpsa_contacts_group_dba($args[0]);
        $qb = new org_openpsa_qbpager('midcom_db_member', 'group_members');
        $qb->add_constraint('gid', '=', $data['group']->id);
        $qb->results_per_page = 10;
        $data['members_qb'] = $qb;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_members($handler_id, array &$data)
    {
        $results = $data['members_qb']->execute();
        if (count($results) > 0)
        {
            midcom_show_style('show-group-persons-header');
            foreach ($results as $member)
            {
                $this->_request_data['member'] = $member;
                $this->_request_data['member_title'] = $member->extra;

                $this->_request_data['person'] = new org_openpsa_contacts_person_dba($member->uid);
                midcom_show_style('show-group-persons-item');
            }
            midcom_show_style('show-group-persons-footer');
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_subgroups($handler_id, array $args, array &$data)
    {
        $group = new org_openpsa_contacts_group_dba($args[0]);
        $qb = org_openpsa_contacts_group_dba::new_query_builder();
        $qb->add_constraint('owner', '=', $group->id);
        $data['results'] = $qb->execute();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_subgroups($handler_id, array &$data)
    {
        if (count($data['results']) > 0)
        {
            midcom_show_style('show-group-subgroups-header');
            foreach ($data['results'] as $subgroup)
            {
                $this->_request_data['subgroup'] = $subgroup;
                midcom_show_style('show-group-subgroups-item');
            }
            midcom_show_style('show-group-subgroups-footer');
        }
    }
}
