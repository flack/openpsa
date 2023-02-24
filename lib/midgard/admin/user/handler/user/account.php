<?php
/**
 * @package midgard.admin.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_user_account extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    private midcom_db_person $person;

    private midcom_core_account $account;

    public function _on_initialize()
    {
        if (!$this->_config->get('allow_manage_accounts')) {
            throw new midcom_error_forbidden('Account management is disabled');
        }
    }

    public function _handler_edit(Request $request, string $handler_id, string $guid, array &$data)
    {
        $this->person = new midcom_db_person($guid);
        $this->person->require_do('midgard:update');
        $this->account = new midcom_core_account($this->person);

        $dm = datamanager::from_schemadb($this->_config->get('schemadb_account'));
        $dm->set_defaults([
            'username' => $this->account->get_username(),
            'person' => $this->person->guid,
            'usertype' => $this->account->get_usertype()
        ]);
        $data['controller'] = $dm->get_controller();

        switch ($data['controller']->handle($request)) {
            case 'save':
                $this->save_account($data['controller']);
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('person %s saved'), $this->person->name));
                return new midcom_response_relocate($this->router->generate('user_edit', ['guid' => $this->person->guid]));

            case 'cancel':
                return new midcom_response_relocate($this->router->generate('user_list'));
        }

        midgard_admin_asgard_plugin::bind_to_object($this->person, $handler_id, $data);

        if ($this->account->get_username() !== '') {
            $data['asgard_toolbar']->add_item([
                MIDCOM_TOOLBAR_URL => $this->router->generate('user_delete_account', ['guid' => $this->person->guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete account'),
                MIDCOM_TOOLBAR_GLYPHICON => 'trash',
            ]);
            $data['view_title'] = $this->_l10n->get('edit account');
        } else {
            $data['view_title'] = $this->_l10n->get('create account');
        }
        $this->add_breadcrumb($this->router->generate('user_list'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('user_edit', ['guid' => $this->person->guid]), $this->person->name);
        $this->add_breadcrumb("", $data['view_title']);

        return $this->get_response('midgard-admin-user-person-edit-account');
    }

    private function save_account(controller $controller)
    {
        $data = $controller->get_form_values();

        if (trim($data['username']) !== '') {
            $this->account->set_username($data['username']);
        }
        if (trim($data['password']) !== '') {
            $this->account->set_password($data['password']);
        }
        $this->account->set_usertype($data['usertype']);
        $this->account->save();
    }

    public function _handler_delete(Request $request, string $handler_id, string $guid, array &$data)
    {
        $this->person = new midcom_db_person($guid);
        $this->person->require_do('midgard:update');
        $this->account = new midcom_core_account($this->person);

        $schemadb = new schemadb(['default' => [
            'operations' => ['delete' => '', 'cancel' => '']
        ]]);
        $dm = new datamanager($schemadb);
        $data['controller'] = $dm->get_controller();

        switch ($data['controller']->handle($request)) {
            case 'delete':
                $this->account->delete();
                // Show confirmation for the user
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('account for %s deleted'), $this->person->name));
                //fall-through
            case 'cancel':
                return new midcom_response_relocate($this->router->generate('user_edit', ['guid' => $this->person->guid]));
        }

        midgard_admin_asgard_plugin::bind_to_object($this->person, $handler_id, $data);
        $data['view_title'] = $this->_l10n->get('delete account');

        $this->add_breadcrumb($this->router->generate('user_list'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('user_edit', ['guid' => $this->person->guid]), $this->person->name);
        $this->add_breadcrumb("", $data['view_title']);

        return $this->get_response('midgard-admin-user-person-delete-account');
    }

    /**
     * Auto-generate passwords on the fly
     */
    public function _handler_passwords(Request $request, array &$data)
    {
        midcom::get()->skip_page_style = true;
        $data['n'] = $request->query->getInt('n', 10);
        $data['length'] = $request->query->getInt('length', 8);
        $data['no_similars'] = $request->query->getBoolean('no_similars', true);
        $data['max_amount'] = (int) $this->_config->get('passwords_max_amount');
        $data['max_length'] = (int) $this->_config->get('passwords_max_length');

        if ($request->query->has('f_submit')) {
            return new Response($this->generate_passwords($data['n'], $data['length'], $data['no_similars'], $data['max_amount'], $data['max_length']));
        }

        return $this->show('midgard-admin-user-generate-passwords');
    }

    private function generate_passwords(int $n, int $length, bool $no_similars, int $max_amount, int $max_length) : string
    {
        if ($n <= 0 || $length <= 0) {
            return $this->_l10n->get('use positive numeric values');
        }
        if ($n > $max_amount || $length > $max_length) {
            return sprintf($this->_l10n->get('only up to %s passwords with maximum length of %s characters'), $max_amount, $max_length);
        }

        $passwords = '';
        for ($i = 0; $i < $n; $i++) {
            $password = midgard_admin_user_plugin::generate_password($length, $no_similars);
            $passwords .= "<input type=\"text\" class=\"plain-text\" value=\"{$password}\" onclick=\"this.select();\" />\n";
        }
        return $passwords;
    }
}
