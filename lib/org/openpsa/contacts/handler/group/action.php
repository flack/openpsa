<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: action.php 26503 2010-07-06 12:00:38Z rambo $
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
    private function _load($identifier)
    {
        $group = new org_openpsa_contacts_group_dba($identifier);

        if (!$group)
        {
            return false;
        }

        return $group;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_notifications($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        // Check if we get the group
        $group = $this->_load($args[0]);
        if (!$group)
        {
            return false;
        }

        $group->require_do('midgard:update');

        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_notifications'));

        $controller = midcom_helper_datamanager2_controller::create('simple');
        $controller->schemadb =& $schemadb;
        $controller->set_storage($group);
        if (! $controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for group {$group->id}.");
            // This will exit.
        }

        switch ($controller->process_form())
        {
            case 'save':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                                   . "group/" . $group->guid . "/");
                // This will exit()

            case 'cancel':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                                   . "group/" . $group->guid . "/");
                // This will exit()
        }

        $this->_request_data['notifications_dm'] =& $controller;
        $this->_request_data['group'] =& $group;

        $_MIDCOM->set_pagetitle($group->official . ": ". $this->_l10n->get("notification settings"));

        org_openpsa_contacts_viewer::add_breadcrumb_path_for_group($this->_request_data['group'], $this);
        $this->add_breadcrumb("", $this->_l10n->get("notification settings"));

        org_openpsa_helpers::dm2_savecancel($this);

        return true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_action($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        // Check if we get the group
        $this->_request_data['group'] = $this->_load($args[0]);
        if (!$this->_request_data['group'])
        {
            return false;
        }

        // Check if the action is a valid one
        $this->_request_data['action'] = $args[1];
        switch ($args[1])
        {
            case "update_member_title":
                // Ajax save handler
                $update_succeeded = false;
                $errstr = NULL;
                if (   array_key_exists('member_title', $_POST)
                    && is_array($_POST['member_title']))
                {
                    foreach ($_POST['member_title'] as $id => $title)
                    {
                        $member = new midcom_db_member($id);
                        if ($member)
                        {
                            $_MIDCOM->auth->require_do('midgard:update', $member);
                            $member->extra = $title;
                            $update_succeeded = $member->update();
                            $errstr = midcom_connection::get_error_string();
                        }
                    }
                }
                $ajax = new org_openpsa_helpers_ajax();
                //This will exit.
                $ajax->simpleReply($update_succeeded, $errstr);

            case "members":
                // Group person listing, always work even if there are none
                $this->_view = "area_group_members";
                return true;

            case "subgroups":
                // Group person listing, always work even if there are none
                $this->_view = "area_group_subgroups";
                return true;

            default:
                return false;
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_notifications($handler_id, &$data)
    {
        midcom_show_style("show-notifications");
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_action($handler_id, &$data)
    {
        switch ($this->_view)
        {
            case "area_group_members":
                // This is most likely a dynamic_load
                $_MIDCOM->load_library('org.openpsa.qbpager');
                $qb = new org_openpsa_qbpager('midcom_db_member', 'group_members');
                $qb->add_constraint('gid', '=', $this->_request_data['group']->id);
                $qb->results_per_page = 10;
                $results = $qb->execute();
                $this->_request_data['members_qb'] = &$qb;

                if (count($results) > 0)
                {
                    midcom_show_style("show-group-persons-header");
                    $_MIDCOM->load_library('org.openpsa.contactwidget');
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
                // This is most likely a dynamic_load
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