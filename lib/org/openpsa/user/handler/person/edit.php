<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Edit person class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_edit extends midcom_baseclasses_components_handler
{
    private midcom_db_person $person;

    public function _handler_edit(Request $request, string $guid, array &$data)
    {
        $this->person = new midcom_db_person($guid);

        if ($this->person->guid != midcom::get()->auth->user->guid) {
            midcom::get()->auth->require_user_do('org.openpsa.user:manage', class: org_openpsa_user_interface::class);
        }

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->person->get_label()));

        $data['controller'] = datamanager::from_schemadb($this->_config->get('schemadb_person'))
            ->set_storage($this->person)
            ->get_controller();

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => $this->save_callback(...)
        ]);
        return $workflow->run($request);
    }

    public function save_callback()
    {
        midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), sprintf($this->_l10n->get('person %s saved'), $this->person->name));
    }
}
