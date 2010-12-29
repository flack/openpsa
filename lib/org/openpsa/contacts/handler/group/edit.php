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
class org_openpsa_contacts_handler_group_edit extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The group we're working on
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_group = null;

    /**
     * The Controller of the organization used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    public function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
    }

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_edit($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        // Check if we get the group
        $this->_group = new org_openpsa_contacts_group_dba($args[0]);
        $this->_group->require_do('midgard:update');

        $data['controller'] = $this->get_controller('simple', $this->_group);

        switch ($data['controller']->process_form())
        {
            case 'save':
                // Index the organization
                $indexer = $_MIDCOM->get_service('indexer');
                org_openpsa_contacts_viewer::index_group($data['controller']->datamanager, $indexer, $this->_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate($prefix . "group/" . $this->_group->guid . "/");
                // This will exit.
        }

        $root_group = org_openpsa_contacts_interface::find_root_group($this->_config);

        if ($this->_group->owner
            && $this->_group->owner != $root_group->id)
        {
            $this->_request_data['parent_group'] = new org_openpsa_contacts_group_dba($this->_group->owner);
        }
        else
        {
            $this->_request_data['parent_group'] = false;
        }

        $this->_request_data['group'] =& $this->_group;

        org_openpsa_helpers::dm2_savecancel($this);
        $_MIDCOM->bind_view_to_object($this->_group);

        $_MIDCOM->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_group->official));

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/ui-elements.css");

        org_openpsa_contacts_viewer::add_breadcrumb_path_for_group($this->_group, $this);
        $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('organization')));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_edit($handler_id, &$data)
    {
        midcom_show_style("show-group-edit");
    }
}
?>