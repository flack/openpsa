<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\controller;
use midcom\datamanager\datamanager;

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_group_create extends midcom_baseclasses_components_handler
{
    /**
     * What type of group are we dealing with, organization or group?
     *
     * @var string
     */
    private $_type;

    /**
     * The group we're working with
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_group;

    /**
     * The parent group, if any
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_parent_group;

    private function load_controller()
    {
        $defaults = [];
        if ($this->_parent_group) {
            $defaults['owner'] = $this->_parent_group->id;
            if ($this->_type == 'organization') {
                // Set the default type to "department"
                $defaults['organization_type'] = org_openpsa_contacts_group_dba::DEPARTMENT;
            }
        }
        return datamanager::from_schemadb($this->_config->get('schemadb_group'))
            ->set_defaults($defaults)
            ->set_storage($this->_group, $this->_type)
            ->get_controller();
    }

    /**
     * @param string $type The type
     * @param string $guid The parent GUID
     */
    public function _handler_create($type, $guid = null)
    {
        $this->_type = $type;

        if (!empty($guid)) {
            // Get the parent organization
            $this->_parent_group = new org_openpsa_contacts_group_dba($guid);
            $this->_parent_group->require_do('midgard:create');
        } else {
            // This is a root level organization, require creation permissions under the component root group
            midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_contacts_group_dba::class);
        }

        $this->_group = new org_openpsa_contacts_group_dba();

        if (   $this->_type == 'organization'
            && $this->_parent_group) {
            $this->_group->owner = $this->_parent_group->id;
        } else {
            $root_group = org_openpsa_contacts_interface::find_root_group();
            $this->_group->owner = $root_group->id;
        }

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_type)));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(controller $controller)
    {
        // Index the organization
        $indexer = new org_openpsa_contacts_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());
        $this->router->generate('group_view', ['guid' => $this->_group->guid]);
    }
}
