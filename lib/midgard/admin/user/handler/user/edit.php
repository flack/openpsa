<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_user_edit extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    private function _prepare_toolbar(array &$data, string $handler_id, midcom_db_person $person)
    {
        if ($this->_config->get('allow_manage_accounts')) {
            $data['asgard_toolbar']->add_item([
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/preferences/{$person->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_i18n->get_string('user preferences', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_GLYPHICON => 'sliders',
            ]);
            $account = new midcom_core_account($person);
            if ($account->get_username() !== '') {
                $data['asgard_toolbar']->add_item([
                    MIDCOM_TOOLBAR_URL => $this->router->generate('user_edit_account', ['guid' => $person->guid]),
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit account'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'user',
                ]);
                $data['asgard_toolbar']->add_item([
                    MIDCOM_TOOLBAR_URL => $this->router->generate('user_delete_account', ['guid' => $person->guid]),
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete account'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'trash',
                ]);
            } else {
                $data['asgard_toolbar']->add_item([
                    MIDCOM_TOOLBAR_URL => $this->router->generate('user_edit_account', ['guid' => $person->guid]),
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create account'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'user-o',
                ]);
            }
            midgard_admin_asgard_plugin::bind_to_object($person, $handler_id, $data);
        }
    }

    public function _handler_edit(Request $request, string $handler_id, string $guid, array &$data)
    {
        $person = new midcom_db_person($guid);
        $person->require_do('midgard:update');
        $dm = datamanager::from_schemadb($this->_config->get('schemadb_person'));
        $dm->set_storage($person);
        $data['controller'] = $dm->get_controller();

        switch ($data['controller']->handle($request)) {
            case 'save':
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('person %s saved'), $person->name));
                return new midcom_response_relocate($this->router->generate('user_edit', ['guid' => $person->guid]));

            case 'cancel':
                return new midcom_response_relocate($this->router->generate('user_list'));
        }

        $data['view_title'] = sprintf($this->_l10n_midcom->get('edit %s'), $person->name);
        $this->_prepare_toolbar($data, $handler_id, $person);
        $this->add_breadcrumb($this->router->generate('user_list'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb("", $data['view_title']);
        return $this->get_response('midgard-admin-user-person-edit');
    }
}
