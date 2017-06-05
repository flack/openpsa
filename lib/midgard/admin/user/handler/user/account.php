<?php
/**
 * @package midgard.admin.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_user_account extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_nullstorage
{
    private $person;

    private $account;

    public function _on_initialize()
    {
        if (!$this->_config->get('allow_manage_accounts')) {
            throw new midcom_error_forbidden('Account management is disabled');
        }
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.user/usermgmt.css');
        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midgard.admin.user'), $this->_request_data);
    }

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_account'));
    }

    public function get_schema_defaults()
    {
        return [
            'username' => $this->account->get_username(),
            'person' => $this->person->guid,
            'usertype' => $this->account->get_usertype()
        ];
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->person = new midcom_db_person($args[0]);
        $this->person->require_do('midgard:update');
        $this->account = new midcom_core_account($this->person);

        $data['controller'] = $this->get_controller('nullstorage');

        switch ($data['controller']->process_form()) {
            case 'save':
                $this->save_account($data['controller']);
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('person %s saved'), $this->person->name));
                return new midcom_response_relocate("__mfa/asgard_midgard.admin.user/edit/{$this->person->guid}/");

            case 'cancel':
                return new midcom_response_relocate('__mfa/asgard_midgard.admin.user/');
        }

        midgard_admin_asgard_plugin::bind_to_object($this->person, $handler_id, $data);

        if ($this->account->get_username() !== '') {
            $data['asgard_toolbar']->add_item([
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midgard.admin.user/account/delete/{$this->person->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete account'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                ]);
            $data['view_title'] = $this->_l10n->get('edit account');
        } else {
            $data['view_title'] = $this->_l10n->get('create account');
        }
        $this->add_breadcrumb("__mfa/asgard_midgard.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("__mfa/asgard_midgard.admin.user/edit/" . $this->person->guid . '/', $this->person->name);
        $this->add_breadcrumb("", $data['view_title']);

        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_edit($handler_id, array &$data)
    {
        $data['person'] = $this->person;
        midcom_show_style('midgard-admin-user-person-edit-account');

        if (isset($_GET['f_submit'])) {
            midcom_show_style('midgard-admin-user-generate-passwords');
        }
    }

    private function save_account(midcom_helper_datamanager2_controller $controller)
    {
        $password = $controller->formmanager->_types['password']->value;
        $username = $controller->formmanager->_types['username']->value;

        if (trim($username) !== '') {
            $this->account->set_username($username);
        }
        if (trim($password) !== '') {
            $this->account->set_password($password);
        }
        $this->account->set_usertype($controller->formmanager->_types['usertype']->convert_to_storage());
        $this->account->save();
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->person = new midcom_db_person($args[0]);
        $this->person->require_do('midgard:update');
        $this->account = new midcom_core_account($this->person);

        $data['controller'] = midcom_helper_datamanager2_handler::get_delete_controller();

        switch ($data['controller']->process_form()) {
            case 'delete':
                $this->account->delete();
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('account for %s deleted'), $this->person->name));
                //fall-through
            case 'cancel':
                return new midcom_response_relocate("__mfa/asgard_midgard.admin.user/edit/{$this->person->guid}/");
        }

        midgard_admin_asgard_plugin::bind_to_object($this->person, $handler_id, $data);
        $data['view_title'] = $this->_l10n->get('delete account');

        $this->add_breadcrumb("__mfa/asgard_midgard.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("__mfa/asgard_midgard.admin.user/edit/" . $this->person->guid . '/', $this->person->name);
        $this->add_breadcrumb("", $data['view_title']);

        return new midgard_admin_asgard_response($this, '_show_delete');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_delete($handler_id, array &$data)
    {
        $data['person'] = $this->person;
        midcom_show_style('midgard-admin-user-person-delete-account');
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
        midcom_show_style('midgard-admin-user-generate-passwords');
    }
}
