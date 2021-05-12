<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Calendar filters handler
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_filters extends midcom_baseclasses_components_handler
{
    /**
     * Handle the request for editing contact list
     */
    public function _handler_edit(Request $request, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        midcom::get()->head->set_pagetitle($this->_l10n->get('choose calendars'));

        // Get the current user
        $person = midcom::get()->auth->user->get_storage();
        $person->require_do('midgard:update');
        // Load the controller
        $data['controller'] = datamanager::from_schemadb($this->_config->get('schemadb_filters'))
            ->set_storage($person)
            ->get_controller();

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback'],
            'relocate' => false
        ]);
        return $workflow->run($request);
    }

    public function save_callback()
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
        midcom::get()->head->add_jscript('openpsa_calendar_widget.refresh();');
    }
}
