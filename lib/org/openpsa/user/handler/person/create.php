<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Create person class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
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

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        $person_schema = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
        $account_schema = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_account'));
        $current = 0;
        $last = sizeof($account_schema['default']->fields);
        foreach ($account_schema['default']->fields as $name => $field) {
            if ($current++ == 0) {
                $field['start_fieldset'] = array(
                    'title' => 'account_fieldset',
                    'css_group' => 'area meta',
                );
            } elseif ($current == $last) {
                $field['end_fieldset'] = '';
            }
            $person_schema['default']->fields[$name] = $field;
            $person_schema['default']->field_order[] = $name;
        }
        $person_schema['default']->validation = $account_schema['default']->validation;
        return $person_schema;
    }

    public function get_schema_defaults()
    {
        $defaults = array();
        if ($this->_group) {
            $defaults['groups'] = array($this->_group->id);
        }
        return $defaults;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        if (count($args) > 0) {
            // Get the organization
            $this->_group = new midcom_db_group($args[0]);
            $this->_group->require_do('midgard:create');
        }

        midcom::get()->head->set_pagetitle($this->_l10n->get('create person'));

        $data['controller'] = $this->get_controller('create');
        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $data['controller'],
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        if ($this->_master->create_account($this->_person, $controller->formmanager)) {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('person %s created'), $this->_person->name));
        }
        return 'view/' . $this->_person->guid . '/';
    }

    /**
     * DM2 creation callback.
     */
    public function & dm2_create_callback(&$controller)
    {
        // Create a new person
        $this->_person = new midcom_db_person;
        if (!$this->_person->create()) {
            debug_print_r('We operated on this object:', $this->_person);
            throw new midcom_error('Failed to create a new person. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_person;
    }
}
