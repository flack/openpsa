<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Calendar filters handler
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_filters extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_filters'));
    }

    /**
     * Handle the request for editing contact list
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        // Get the current user
        $this->_person = new midcom_db_person(midcom_connection::get_user());
        $this->_person->require_do('midgard:update');

        // Load the controller
        $data['controller'] = $this->get_controller('simple', $this->_person);

        // Process the form
        switch ($data['controller']->process_form()) {
            case 'save':
            case 'cancel':
                if (isset($_GET['org_openpsa_calendar_returnurl'])) {
                    $url = $_GET['org_openpsa_calendar_returnurl'];
                } else {
                    $url = '';
                }
                return new midcom_response_relocate($url);
        }

        // Add the breadcrumb pieces
        if (isset($_GET['org_openpsa_calendar_returnurl'])) {
            $this->add_breadcrumb($_GET['org_openpsa_calendar_returnurl'], $this->_l10n->get('calendar'));
        }

        $this->add_breadcrumb('filters/', $this->_l10n->get('choose calendars'));
    }

    /**
     * Show the contact editing interface
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_edit($handler_id, array &$data)
    {
        $data['person'] = $this->_person;

        midcom_show_style('calendar-filter-chooser');
    }
}
