<?php
/**
 * @package org.openpsa.reports
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.reports site interface class.
 *
 * Reporting interfaces to various org.openpsa components
 *
 * @package org.openpsa.reports
 */
class org_openpsa_reports_viewer extends midcom_baseclasses_components_request
{
    /**
     * Array of available report generators
     *
     * @var array
     */
    private $_available_generators;

    public function _on_initialize()
    {
        $components = $this->_get_available_generators();
        foreach (array_keys($components) as $component) {
            $parts = explode('.', $component);
            $last = array_pop($parts);

            // Match /xxx/get
            $this->_request_switch["{$last}_report_get"] = array
            (
                'fixed_args' => array($last, 'get'),
                'handler' => array("org_openpsa_reports_handler_{$last}_report", 'generator_get'),
            );

            // Match /xxx/<edit>/<guid>
            $this->_request_switch["{$last}_edit_report_guid"] = array
            (
                'fixed_args' => array($last, 'edit'),
                'variable_args' => 1,
                'handler' => array("org_openpsa_reports_handler_{$last}_report", 'query_form'),
            );

            // Match /xxx/<guid>/<filename>
            $this->_request_switch["{$last}_report_guid_file"] = array
            (
                'fixed_args' => array($last),
                'variable_args' => 2,
                'handler' => array("org_openpsa_reports_handler_{$last}_report", 'generator'),
            );

            // Match /xxx/<guid>
            $this->_request_switch["{$last}_report_guid"] = array
            (
                'fixed_args' => array($last),
                'variable_args' => 1,
                'handler' => array("org_openpsa_reports_handler_{$last}_report", 'generator'),
            );

            // Match /xxx
            $this->_request_switch["{$last}_report"] = array
            (
                'fixed_args' => array($last),
                'handler' => array("org_openpsa_reports_handler_{$last}_report", 'query_form'),
            );
        }

        // Match /csv/<filename>
        $this->_request_switch['csv_export'] = array
        (
            'fixed_args'    => 'csv',
            'variable_args' => 1,
            'handler'       => 'csv',
        );

        // Match /delete/<guid>
        $this->_request_switch['delete_report'] = array
        (
            'fixed_args'    => 'delete',
            'variable_args' => 1,
            'handler'       => 'delete_report',
        );

        // Match /
        $this->_request_switch['frontpage'] = array
        (
            'handler' => 'frontpage'
        );
    }

    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();
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

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_frontpage($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        $data['available_components'] = $this->_get_available_generators();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_frontpage($handler_id, array &$data)
    {
        midcom_show_style('show-frontpage');
    }

    private function _get_available_generators()
    {
        if (is_array($this->_available_generators)) {
            return $this->_available_generators;
        }
        $this->_available_generators = array();

        $components = array
        (
            // TODO: better localization strings
            'org.openpsa.projects' => $this->_i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'),
            'org.openpsa.sales' => $this->_i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'),
            'org.openpsa.invoices' => $this->_i18n->get_string('org.openpsa.invoices', 'org.openpsa.invoices'),
        );

        $siteconfig = org_openpsa_core_siteconfig::get_instance();

        foreach ($components as $component => $loc) {
            $node_guid = $siteconfig->get_node_guid($component);
            try {
                midcom_db_topic::get_cached($node_guid);
                $this->_available_generators[$component] = $loc;
            } catch (midcom_error $e) {
                debug_add("topic for component '{$component}' not found or accessible");
            }
        }

        $this->_available_generators = $components;
        return $this->_available_generators;
    }
}
