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
implements midcom_helper_datamanager2_interfaces_edit
{
    private $_person;

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'), $this->_request_data);
    }

    private function _prepare_toolbar(&$data, $handler_id)
    {
        if ($this->_config->get('allow_manage_accounts')) {
            $data['asgard_toolbar']->add_item(array(
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/preferences/{$this->_person->guid}/",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('user preferences', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/configuration.png',
            ));
            $account = new midcom_core_account($this->_person);
            if ($account->get_username() !== '') {
                $data['asgard_toolbar']->add_item(array(
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/account/{$this->_person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person.png',
                ));
                $data['asgard_toolbar']->add_item(array(
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/account/delete/{$this->_person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                ));
            } else {
                $data['asgard_toolbar']->add_item(array(
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/account/{$this->_person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
                ));
            }
            midgard_admin_asgard_plugin::bind_to_object($this->_person, $handler_id, $data);
        }
    }

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
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
}
