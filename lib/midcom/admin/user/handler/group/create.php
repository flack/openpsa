<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * group creation class
 *
 * @package midcom.admin.user
 */
class midcom_admin_user_handler_group_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    private $_group = null;

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'), $this->_request_data);
    }

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    /**
     * DM2 creation callback, creates a new group and binds it to the selected group.
     *
     * Assumes Admin Privileges.
     */
    public function & dm2_create_callback (&$controller)
    {
        // Create a new group
        $this->_group = new midcom_db_group();
        if (! $this->_group->create())
        {
            debug_print_r('We operated on this object:', $this->_group);
            throw new midcom_error('Failed to create a new group. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_group;
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $data['controller'] = $this->get_controller('create');
        switch ($data['controller']->process_form())
        {
            case 'save':
                // Show confirmation for the group
                midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('group %s saved'), $this->_group->name));
                return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/group/edit/{$this->_group->guid}/");

            case 'cancel':
                return new midcom_response_relocate('__mfa/asgard_midcom.admin.user/');
        }

        $data['view_title'] = $this->_l10n->get('create group');

        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/group/create/", $data['view_title']);
        return new midgard_admin_asgard_response($this, '_show_create');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_create($handler_id, array &$data)
    {
        $data['group'] = $this->_group;
        midcom_show_style('midcom-admin-user-group-create');
    }
}
