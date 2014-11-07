<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_notifications extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    public function load_schemadb()
    {
        $notifier = new org_openpsa_notifications;
        return $notifier->load_schemadb();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_notifications($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        $group = new org_openpsa_contacts_group_dba($args[0]);
        $group->require_do('midgard:update');

        $controller = $this->get_controller('simple', $group);

        switch ($controller->process_form())
        {
            case 'save':
                // Fall-through
            case 'cancel':
                return new midcom_response_relocate("group/" . $group->guid . "/");
        }

        $data['notifications_dm'] = $controller;
        $data['object'] = $group;

        midcom::get()->head->set_pagetitle($group->get_label() . ": ". $this->_l10n->get("notification settings"));

        $this->add_breadcrumb('group/' . $group->guid . '/', $group->get_label());
        $this->add_breadcrumb("", $this->_l10n->get("notification settings"));

        org_openpsa_helpers::dm2_savecancel($this);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_notifications($handler_id, array &$data)
    {
        midcom_show_style('show-notifications');
    }
}
