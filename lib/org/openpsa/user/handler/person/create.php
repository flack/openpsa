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
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        $person_schema = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
        $account_schema = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_account'));
        foreach ($account_schema['default']->fields as $name => $value)
        {
            $person_schema['default']->fields[$name] = $value;
            $person_schema['default']->field_order[] = $name;
        }
        $person_schema['default']->validation = $account_schema['default']->validation;
        return $person_schema;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        $data['controller'] = $this->get_controller('create');
        switch ($data['controller']->process_form())
        {
            case 'save':
                $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.user'), sprintf($this->_l10n->get('person %s created'), $this->_person->name));
                $this->_master->create_account($this->_person, $data["controller"]->formmanager);

                $_MIDCOM->relocate('view/' . $this->_person->guid . '/');

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit.
        }

        $this->add_breadcrumb('', sprintf($this->_l10n->get('create person')));

        org_openpsa_helpers::dm2_savecancel($this);
    }


    /**
     * DM2 creation callback.
     */
    function & dm2_create_callback (&$controller)
    {
        // Create a new person
        $this->_person = new midcom_db_person();
        if (! $this->_person->create())
        {
            debug_print_r('We operated on this object:', $this->_person);
            throw new midcom_error('Failed to create a new person. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_person;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        midcom_show_style("show-person-create");
    }

}
?>