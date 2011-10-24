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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_action($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        // Check if we get the group
        $data['group'] = new org_openpsa_contacts_group_dba($args[0]);

        // Check if the action is a valid one
        $data['action'] = $args[1];
        switch ($args[1])
        {
            case "update_member_title":
                // Ajax save handler
                $update_succeeded = false;
                $errstr = null;
                if (   array_key_exists('member_title', $_POST)
                    && is_array($_POST['member_title']))
                {
                    foreach ($_POST['member_title'] as $id => $title)
                    {
                        $update_succeeded = false;
                        try
                        {
                            $member = new midcom_db_member($id);
                            $member->require_do('midgard:update');
                            $member->extra = $title;
                            $update_succeeded = $member->update();
                        }
                        catch (midcom_error $e)
                        {
                            $e->log();
                        }
                        $errstr = midcom_connection::get_error_string();
                    }
                }
                $ajax = new org_openpsa_helpers_ajax();
                //This will exit.
                $ajax->simpleReply($update_succeeded, $errstr);

            case "members":
                // Group person listing, always work even if there are none
                $this->_view = "area_group_members";
                break;

            case "subgroups":
                // Group person listing, always work even if there are none
                $this->_view = "area_group_subgroups";
                break;

            default:
                throw new midcom_error('Unknown action ' . $args[1]);
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_action($handler_id, array &$data)
    {
        switch ($this->_view)
        {
            case "area_group_members":
                // This is most likely a dynamic_load
                $qb = new org_openpsa_qbpager('midcom_db_member', 'group_members');
                $qb->add_constraint('gid', '=', $this->_request_data['group']->id);
                $qb->results_per_page = 10;
                $results = $qb->execute();
                $this->_request_data['members_qb'] = &$qb;

                if (count($results) > 0)
                {
                    midcom_show_style("show-group-persons-header");
                    foreach ($results as $member)
                    {
                        $this->_request_data['member'] = $member;

                        if ($member->extra == "")
                        {
                            $member->extra = $this->_l10n->get('<title>');
                        }
                        $this->_request_data['member_title'] = $member->extra;

                        $this->_request_data['person'] = new org_openpsa_contacts_person_dba($member->uid);
                        midcom_show_style("show-group-persons-item");
                    }
                    midcom_show_style("show-group-persons-footer");
                }
                break;
            case "area_group_subgroups":
                $qb = org_openpsa_contacts_group_dba::new_query_builder();
                $qb->add_constraint('owner', '=', $this->_request_data['group']->id);
                $results = $qb->execute();
                if (count($results) > 0)
                {
                    midcom_show_style("show-group-subgroups-header");
                    foreach ($results as $subgroup)
                    {
                        $this->_request_data['subgroup'] = $subgroup;
                        midcom_show_style("show-group-subgroups-item");
                    }
                    midcom_show_style("show-group-subgroups-footer");
                }
                break;
        }
    }
}
?>