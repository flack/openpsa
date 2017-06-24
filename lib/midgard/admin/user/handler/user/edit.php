<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_user_edit extends midcom_baseclasses_components_handler
{
    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.user/usermgmt.css');
        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midgard.admin.user'), $this->_request_data);
    }

    private function _prepare_toolbar(&$data, $handler_id, midcom_db_person $person)
    {
        if ($this->_config->get('allow_manage_accounts')) {
            $data['asgard_toolbar']->add_item([
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/preferences/{$person->guid}/",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('user preferences', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/configuration.png',
            ]);
            $account = new midcom_core_account($person);
            if ($account->get_username() !== '') {
                $data['asgard_toolbar']->add_item([
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midgard.admin.user/account/{$person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person.png',
                ]);
                $data['asgard_toolbar']->add_item([
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midgard.admin.user/account/delete/{$person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                ]);
            } else {
                $data['asgard_toolbar']->add_item([
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midgard.admin.user/account/{$person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
                ]);
            }
            midgard_admin_asgard_plugin::bind_to_object($person, $handler_id, $data);
        }
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $person = new midcom_db_person($args[0]);
        $person->require_do('midgard:update');
        $dm = datamanager::from_schemadb($this->_config->get('schemadb_person'));
        $dm->set_storage($person);
        $data['controller'] = $dm->get_controller();

        switch ($data['controller']->process()) {
            case 'save':
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('person %s saved'), $person->name));
                return new midcom_response_relocate("__mfa/asgard_midgard.admin.user/edit/{$person->guid}/");

            case 'cancel':
                return new midcom_response_relocate('__mfa/asgard_midgard.admin.user/');
        }

        $data['person'] = $person;
        $data['view_title'] = sprintf($this->_l10n->get('edit %s'), $person->name);
        $this->_prepare_toolbar($data, $handler_id, $person);
        $this->add_breadcrumb("__mfa/asgard_midgard.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("", $data['view_title']);
        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('midgard-admin-user-person-edit');
    }
}
