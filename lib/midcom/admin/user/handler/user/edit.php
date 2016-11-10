<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.admin.user
 */
class midcom_admin_user_handler_user_edit extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_nullstorage
{
    private $_person;

    private $_account;

    private $_schemadb_name = 'schemadb_person';

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'), $this->_request_data);
    }

    private function _prepare_toolbar(&$data, $handler_id)
    {
        if (   $this->_config->get('allow_manage_accounts')
            && $this->_person) {
            $this->_account = new midcom_core_account($this->_person);
            $buttons = array
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard/preferences/{$this->_person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('user preferences', 'midgard.admin.asgard'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/configuration.png',
                ),
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/account/{$this->_person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                )
            );
            if (($this->_account->get_username() !== '')) {
                $buttons[] = array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/delete_account/{$this->_person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                );
            }
            $data['asgard_toolbar']->add_items($buttons);
            midgard_admin_asgard_plugin::bind_to_object($this->_person, $handler_id, $data);
        }
    }

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get($this->_schemadb_name));
        if ($this->_schemadb_name == 'schemadb_account') {
            if (   isset($_POST['username'])
                && trim($_POST['username']) == '') {
                // Remove the password requirement if there is no username present
                foreach ($schemadb as $key => $schema) {
                    if (isset($schema->fields['password'])) {
                        $schemadb[$key]->fields['password']['widget_config']['require_password'] = false;
                    }
                }
            }
        }
        return $schemadb;
    }

    public function get_schema_defaults()
    {
        $defaults = array
        (
            'username' => $this->_account->get_username(),
            'person' => $this->_person->guid
        );

        if (!extension_loaded('midgard')) {
            $defaults['usertype'] = $this->_account->get_usertype();
        }

        return $defaults;
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do('midgard:update');

        $data['controller'] = $this->get_controller('simple', $this->_person);

        switch ($data['controller']->process_form()) {
            case 'save':
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('person %s saved'), $this->_person->name));
                return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/edit/{$this->_person->guid}/");

            case 'cancel':
                return new midcom_response_relocate('__mfa/asgard_midcom.admin.user/');
        }

        $data['view_title'] = sprintf($this->_l10n->get('edit %s'), $this->_person->name);
        $this->_prepare_toolbar($data, $handler_id);
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("", $data['view_title']);
        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_edit($handler_id, array &$data)
    {
        $data['person'] = $this->_person;
        midcom_show_style('midcom-admin-user-person-edit');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_edit_account($handler_id, array $args, array &$data)
    {
        if (!$this->_config->get('allow_manage_accounts')) {
            throw new midcom_error('Account management is disabled');
        }

        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do('midgard:update');
        $this->_account = new midcom_core_account($this->_person);
        $this->_schemadb_name = 'schemadb_account';

        $data['controller'] = $this->get_controller('nullstorage');

        switch ($data['controller']->process_form()) {
            case 'save':
                $this->_save_account($data['controller']);
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('person %s saved'), $this->_person->name));
                return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/edit/{$this->_person->guid}/");

            case 'cancel':
                return new midcom_response_relocate('__mfa/asgard_midcom.admin.user/');
        }

        $data['view_title'] = sprintf($this->_l10n->get('edit %s'), $this->_person->name);
        midgard_admin_asgard_plugin::bind_to_object($this->_person, $handler_id, $data);

        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/edit/" . $this->_person->guid . '/', $this->_person->name);
        $this->add_breadcrumb("", $data['view_title']);

        return new midgard_admin_asgard_response($this, '_show_edit_account');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_edit_account($handler_id, array &$data)
    {
        $data['person'] = $this->_person;
        midcom_show_style('midcom-admin-user-person-edit-account');

        if (isset($_GET['f_submit'])) {
            midcom_show_style('midcom-admin-user-generate-passwords');
        }
    }

    private function _save_account(midcom_helper_datamanager2_controller $controller)
    {
        $password = $controller->formmanager->_types['password']->value;
        $username = $controller->formmanager->_types['username']->value;

        if (trim($username) !== '') {
            $this->_account->set_username($username);
        }
        if (trim($password) !== '') {
            $this->_account->set_password($password);
        }
        if (!extension_loaded('midgard')) {
            $this->_account->set_usertype($controller->formmanager->_types['usertype']->convert_to_storage());
        }
        $this->_account->save();
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_delete_account($handler_id, array $args, array &$data)
    {
        if (!$this->_config->get('allow_manage_accounts')) {
            throw new midcom_error('Account management is disabled');
        }

        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do('midgard:update');
        $this->_account = new midcom_core_account($this->_person);

        $data['controller'] = midcom_helper_datamanager2_handler::get_delete_controller();

        switch ($data['controller']->process_form()) {
            case 'delete':
                $this->_account->delete();
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('account for %s deleted'), $this->_person->name));
                //fall-through
            case 'cancel':
            return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/edit/{$this->_person->guid}/");
        }

        midgard_admin_asgard_plugin::bind_to_object($this->_person, $handler_id, $data);
        $data['view_title'] = $this->_l10n->get('delete account');

        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/edit/" . $this->_person->guid . '/', $this->_person->name);
        $this->add_breadcrumb("", $data['view_title']);

        return new midgard_admin_asgard_response($this, '_show_delete_account');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_delete_account($handler_id, array &$data)
    {
        $data['person'] = $this->_person;
        midcom_show_style('midcom-admin-user-person-delete-account');
    }

    /**
     * Auto-generate passwords on the fly
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_passwords($handler_id, array $args, array &$data)
    {
        midcom::get()->skip_page_style = true;
    }

    /**
     * Auto-generate passwords on the fly
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_passwords($handler_id, array &$data)
    {
        midcom_show_style('midcom-admin-user-generate-passwords');
    }
}
