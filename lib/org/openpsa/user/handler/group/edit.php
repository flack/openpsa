<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Edit group class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_edit extends midcom_baseclasses_components_handler
{
    /**
     * The person we're working on
     *
     * @var midcom_db_group
     */
    private $group;

    /**
     * @param array $args The argument list.
     */
    public function _handler_edit(array $args)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class);
        $this->group = new midcom_db_group($args[0]);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->group->get_label()));

        $controller = datamanager::from_schemadb($this->_config->get('schemadb_group'))
            ->set_storage($this->group)
            ->get_controller();

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback()
    {
        midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), sprintf($this->_l10n->get('group %s saved'), $this->group->get_label()));
    }
}
