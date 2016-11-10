<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_group_edit extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * What type of group are we dealing with, organization or group?
     *
     * @var string
     */
    private $_type;

    /**
     * The group we're working on
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_group = null;

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    public function get_schema_name()
    {
        return $this->_type;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_group = new org_openpsa_contacts_group_dba($args[0]);
        $this->_group->require_do('midgard:update');

        if ($this->_group->orgOpenpsaObtype < org_openpsa_contacts_group_dba::MYCONTACTS) {
            $this->_type = 'group';
        } else {
            $this->_type = 'organization';
        }

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get($this->_type)));

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $this->get_controller('simple', $this->_group),
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        // Index the organization
        $indexer = new org_openpsa_contacts_midcom_indexer($this->_topic);
        $indexer->index($controller->datamanager);
        return "group/" . $this->_group->guid . "/";
    }
}
