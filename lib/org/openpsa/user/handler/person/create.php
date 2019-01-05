<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * Create person class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_create extends midcom_baseclasses_components_handler
{
    use org_openpsa_user_handler;

    /**
     * The person we're working on
     *
     * @var midcom_db_person
     */
    private $_person;

    /**
     * The group for our new person, if any
     *
     * @var midcom_db_group
     */
    private $_group;

    private function load_controller()
    {
        $person_schema = schemadb::from_path($this->_config->get('schemadb_person'));
        $account_schema = schemadb::from_path($this->_config->get('schemadb_account'));
        $person_fields = $person_schema->get('default')->get('fields');
        $current = 0;
        $last = count($account_schema->get('default')->get('fields'));
        foreach ($account_schema->get('default')->get('fields') as $name => $field) {
            if ($current++ == 0) {
                $field['start_fieldset'] = [
                    'title' => 'account_fieldset',
                    'css_group' => 'area meta',
                ];
            } elseif ($current == $last) {
                $field['end_fieldset'] = '';
            }
            $person_fields[$name] = $field;
        }
        $person_schema->get('default')->set('fields', $person_fields);
        $person_schema->get('default')->set('validation', $account_schema->get('default')->get('validation'));

        $defaults = [];
        if ($this->_group) {
            $defaults['groups'] = [$this->_group->id];
        }
        $dm = new datamanager($person_schema);

        return $dm
            ->set_defaults($defaults)
            ->set_storage($this->_person)
            ->get_controller();
    }

    /**
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create(array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class);

        if (count($args) > 0) {
            // Get the organization
            $this->_group = new midcom_db_group($args[0]);
            $this->_group->require_do('midgard:create');
        }
        $this->_person = new midcom_db_person;

        midcom::get()->head->set_pagetitle($this->_l10n->get('create person'));

        $data['controller'] = $this->load_controller();
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(controller $controller)
    {
        if ($this->create_account($this->_person, $controller->get_form_values())) {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('person %s created'), $this->_person->name));
        }
        return $this->router->generate('user_view', ['guid' => $this->_person->guid]);
    }
}
