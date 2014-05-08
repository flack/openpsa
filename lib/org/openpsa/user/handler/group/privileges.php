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
 * @package org.openpsa.contacts
 */
class org_openpsa_user_handler_group_privileges extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The group we're working with, if any
     *
     * @var midcom_db_group
     */
    private $_group = null;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_acl'));

        $fields =& $schemadb['default']->fields;

        $group_object = midcom::get('auth')->get_group("group:{$this->_request_data['group']->guid}");

        // Get the calendar root event
        $root_event = org_openpsa_calendar_interface::find_root_event();
        if (is_object($root_event))
        {
            $fields['calendar']['privilege_object'] = $root_event;
            $fields['calendar']['privilege_assignee'] = $group_object->id;
        }
        else
        {
            unset($fields['calendar']);
        }

        // Set the group into ACL
        $fields['contact_creation']['privilege_object'] =  $group_object->get_storage();
        $fields['contact_editing']['privilege_object'] =  $group_object->get_storage();

        $fields['organization_creation']['privilege_object'] = $group_object->get_storage();
        $fields['organization_editing']['privilege_object'] = $group_object->get_storage();

        $fields['projects']['privilege_object'] = $group_object->get_storage();
        $fields['invoices_creation']['privilege_object'] = $group_object->get_storage();
        $fields['invoices_editing']['privilege_object'] = $group_object->get_storage();
        $fields['campaigns_creation']['privilege_object'] = $group_object->get_storage();
        $fields['campaigns_editing']['privilege_object'] = $group_object->get_storage();
        $fields['salesproject_creation']['privilege_object'] = $group_object->get_storage();
        return $schemadb;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_privileges($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        // Check if we get the group
        $this->_group = new midcom_db_group($args[0]);
        $this->_group->require_do('midgard:privileges');

        $data['group'] = $this->_group;

        $data['acl_dm'] = $this->get_controller('simple', $this->_group);

        switch ($data['acl_dm']->process_form())
        {
            case 'save':
                // Fall-through
            case 'cancel':
                return new midcom_response_relocate("group/" . $this->_group->guid . "/");
        }

        midcom::get('head')->set_pagetitle($this->_group->official);
        org_openpsa_helpers::dm2_savecancel($this);

        $this->add_breadcrumb("group/{$this->_group->guid}/", $this->_group->name);
        $this->add_breadcrumb("", $this->_l10n->get('permissions'));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_privileges($handler_id, array &$data)
    {
        midcom_show_style("show-privileges");
    }
}
?>