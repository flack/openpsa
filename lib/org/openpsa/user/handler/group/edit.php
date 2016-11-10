<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Edit group class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_edit extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The person we're working on
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
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        $this->_group = new midcom_db_group($args[0]);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_group->get_label()));

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $this->get_controller('simple', $this->_group),
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), sprintf($this->_l10n->get('group %s saved'), $this->_group->get_label()));
    }
}
