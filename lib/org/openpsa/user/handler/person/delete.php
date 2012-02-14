<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Delete person class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_delete extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_view
{
    /**
     * The person we're working on
     *
     * @var midcom_db_person
     */
    private $_person;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_person = new midcom_db_person($args[0]);
        if ($this->_person->id != midcom_connection::get_user())
        {
            midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        }

        if (array_key_exists('org_openpsa_user_deleteok', $_POST))
        {
            $delete_succeeded = $this->_person->delete();
            if ($delete_succeeded)
            {
                // Update the index
                $indexer = $_MIDCOM->get_service('indexer');
                $indexer->delete($this->_person->guid);

                $_MIDCOM->relocate('');
                // This will exit
            }
            else
            {
                // Failure, give a message
                midcom::get('uimessages')->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("failed to delete person, reason") . ' ' . midcom_connection::get_error_string(), 'error');
                $_MIDCOM->relocate('view/' . $this->_person->guid . '/');
                // This will exit
            }
        }

        $data['view'] = midcom_helper_datamanager2_handler::get_view_controller($this, $this->_person);
        $data['person'] = $this->_person;

        $this->add_breadcrumb('', sprintf($this->_l10n_midcom->get('delete %s'), $this->_person->get_label()));

        midcom::get()->bind_view_to_object($this->_person);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, array &$data)
    {
        midcom_show_style("show-person-delete");
    }

}
?>