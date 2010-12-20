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
    public function _on_initialize()
    {
        $components = org_openpsa_reports_viewer::available_component_generators();
        foreach ($components as $component => $loc)
        {
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

    public function _on_handle($handler, $args)
    {
        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();
        return true;
    }

    /**
     * The CSV handlers return a posted variable with correct headers
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_delete_report($handler_id, $args, &$data)
    {
        $report = $this->load_object('org_openpsa_reports_query_dba', $args[0]);
        $report->delete();
        $_MIDCOM->relocate();

        return true;
    }


    /**
     * The CSV handlers return a posted variable with correct headers
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_csv($handler_id, $args, &$data)
    {
        if ( !isset($_POST['org_openpsa_reports_csv']) )
        {
            debug_add('Variable org_openpsa_reports_csv not set in _POST, aborting');
            return false;
        }

        //We're outputting CSV
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->content_type('application/csv');

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_csv($handler_id, &$data)
    {
        echo $_POST['org_openpsa_reports_csv'];
        return true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_frontpage($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $data['available_components'] = org_openpsa_reports_viewer::available_component_generators();

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_frontpage($handler_id, &$data)
    {
        midcom_show_style('show-frontpage');

        return true;
    }

    function available_component_generators()
    {
        if (!isset($GLOBALS['available_component_generators_components_checked']))
        {
            $GLOBALS['available_component_generators_components_checked'] = false;
        }
        $components_checked =& $GLOBALS['available_component_generators_components_checked'];
        if (!isset($GLOBALS['available_component_generators_components']))
        {
            $GLOBALS['available_component_generators_components'] = array
            (
                // TODO: better localization strings
                'org.openpsa.projects' => $_MIDCOM->i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'),
                'org.openpsa.sales' => $_MIDCOM->i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'),
                'org.openpsa.invoices' => $_MIDCOM->i18n->get_string('org.openpsa.invoices', 'org.openpsa.invoices'),
                //'org.openpsa.directmarketing' => $_MIDCOM->i18n->get_string('org.openpsa.directmarketing', 'org.openpsa.reports'),
            );
        }
        $components =& $GLOBALS['available_component_generators_components'];
        if ($components_checked)
        {
            reset($components);
            return $components;
        }
        $siteconfig = org_openpsa_core_siteconfig::get_instance();

        foreach ($components as $component => $loc)
        {
            $node_guid = $siteconfig->get_node_guid($component);
            $topic = midcom_db_topic::get_cached($node_guid);

            if (   empty($topic)
                || !$topic->can_do('midgard:read'))
            {
                debug_add("topic for component '{$component}' not found or accessible");
                unset ($components[$component]);
            }
        }
        $components_checked = true;
        reset($components);

        return $components;
    }
}
?>