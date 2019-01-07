<?php
/**
 * @package org.openpsa.reports
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Common handlers
 *
 * @package org.openpsa.reports
 */
class org_openpsa_reports_handler_common extends midcom_baseclasses_components_handler
{
    /**
     * @param array $data The local request data.
     */
    public function _handler_frontpage(array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $data['available_components'] = org_openpsa_reports_viewer::get_available_generators();
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/' . $this->_component . '/common.css');

        return $this->show('show-frontpage');
    }

    /**
     * Delete the given report and redirect to front page
     *
     * @param array $args The argument list.
     */
    public function _handler_delete_report(array $args)
    {
        $report = new org_openpsa_reports_query_dba($args[0]);
        $report->delete();
        return new midcom_response_relocate('');
    }

    /**
     * The CSV handlers return a posted variable with correct headers
     *
     * @param Request $request The request object
     */
    public function _handler_csv(Request $request)
    {
        if (!$request->request->has('org_openpsa_reports_csv')) {
            throw new midcom_error('Variable org_openpsa_reports_csv not set in _POST');
        }

        //We're outputting CSV
        midcom::get()->skip_page_style = true;
        midcom::get()->header('Content-type: application/csv');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_csv($handler_id, array &$data)
    {
        echo $_POST['org_openpsa_reports_csv'];
    }
}
