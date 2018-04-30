<?php
/**
 * @package org.openpsa.reports
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Common handlers
 *
 * @package org.openpsa.reports
 */
class org_openpsa_reports_handler_common extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_frontpage($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $data['available_components'] = org_openpsa_reports_viewer::get_available_generators();
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/' . $this->_component . '/common.css');

        return $this->show('show-frontpage');
    }

    /**
     * Delete the given report and redirect to front page
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete_report($handler_id, array $args, array &$data)
    {
        $report = new org_openpsa_reports_query_dba($args[0]);
        $report->delete();
        return new midcom_response_relocate('');
    }

    /**
     * The CSV handlers return a posted variable with correct headers
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_csv($handler_id, array $args, array &$data)
    {
        if (!isset($_POST['org_openpsa_reports_csv'])) {
            throw new midcom_error('Variable org_openpsa_reports_csv not set in _POST');
        }

        //We're outputting CSV
        midcom::get()->skip_page_style = true;
        midcom::get()->header('Content-type: application/csv');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_csv($handler_id, array &$data)
    {
        echo $_POST['org_openpsa_reports_csv'];
    }
}
