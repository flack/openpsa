<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * org.openpsa.contacts person handler class.
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_notifications extends midcom_baseclasses_components_handler
{
    /**
     * @param string $guid The person GUID
     */
    public function _handler_notifications(Request $request, $guid)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class);

        $person = new org_openpsa_contacts_person_dba($guid);
        $person->require_do('midgard:update');

        midcom::get()->head->set_pagetitle($this->_l10n->get("notification settings"));

        $notifier = new org_openpsa_notifications;
        $dm = $notifier
            ->load_datamanager()
            ->set_storage($person);

        $workflow = $this->get_workflow('datamanager', ['controller' => $dm->get_controller()]);
        return $workflow->run($request);
    }
}
