<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Create group class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The group we're working on
     *
     * @var midcom_db_group
     */
    private $_group;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        midcom::get()->head->set_pagetitle($this->_l10n->get('create group'));

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $this->get_controller('create'),
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), sprintf($this->_l10n->get('group %s saved'), $this->_group->name));
        return 'group/' . $this->_group->guid . '/';
    }

    /**
     * DM2 creation callback.
     */
    public function & dm2_create_callback(&$controller)
    {
        // Create a new group
        $this->_group = new midcom_db_group();
        if (!$this->_group->create()) {
            debug_print_r('We operated on this object:', $this->_group);
            throw new midcom_error('Failed to create a new group. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_group;
    }
}
