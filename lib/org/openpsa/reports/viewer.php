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
class org_openpsa_reports_viewer extends midcom_baseclasses_components_viewer
{
    public function _on_initialize()
    {
        $components = self::get_available_generators();
        foreach (array_keys($components) as $component) {
            $parts = explode('.', $component);
            $last = array_pop($parts);

            // Match /xxx/get
            $this->_request_switch["{$last}_report_get"] = [
                'fixed_args' => [$last, 'get'],
                'handler' => ["org_openpsa_reports_handler_{$last}_report", 'generator_get'],
            ];

            // Match /xxx/<edit>/<guid>
            $this->_request_switch["{$last}_edit_report_guid"] = [
                'fixed_args' => [$last, 'edit'],
                'variable_args' => 1,
                'handler' => ["org_openpsa_reports_handler_{$last}_report", 'query_form'],
            ];

            // Match /xxx/<guid>/<filename>
            $this->_request_switch["{$last}_report_guid_file"] = [
                'fixed_args' => [$last],
                'variable_args' => 2,
                'handler' => ["org_openpsa_reports_handler_{$last}_report", 'generator'],
            ];

            // Match /xxx/<guid>
            $this->_request_switch["{$last}_report_guid"] = [
                'fixed_args' => [$last],
                'variable_args' => 1,
                'handler' => ["org_openpsa_reports_handler_{$last}_report", 'generator'],
            ];

            // Match /xxx
            $this->_request_switch["{$last}_report"] = [
                'fixed_args' => [$last],
                'handler' => ["org_openpsa_reports_handler_{$last}_report", 'query_form'],
            ];
        }

        // Match /csv/<filename>
        $this->_request_switch['csv_export'] = [
            'fixed_args'    => 'csv',
            'variable_args' => 1,
            'handler'       => [org_openpsa_reports_handler_common::class, 'csv'],
        ];

        // Match /delete/<guid>
        $this->_request_switch['delete_report'] = [
            'fixed_args'    => 'delete',
            'variable_args' => 1,
            'handler'       => [org_openpsa_reports_handler_common::class, 'delete_report'],
        ];

        // Match /
        $this->_request_switch['frontpage'] = [
            'handler' => [org_openpsa_reports_handler_common::class, 'frontpage']
        ];
    }

    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();
    }

    public static function get_available_generators() : array
    {
        static $available_generators;
        if (is_array($available_generators)) {
            return $available_generators;
        }
        $available_generators = [];

        $components = [
            'org.openpsa.projects',
            'org.openpsa.sales',
            'org.openpsa.invoices'
        ];

        $siteconfig = org_openpsa_core_siteconfig::get_instance();

        foreach ($components as $component) {
            $node_guid = $siteconfig->get_node_guid($component);
            try {
                midcom_db_topic::get_cached($node_guid);
                $available_generators[$component] = midcom::get()->i18n->get_string($component, $component);
            } catch (midcom_error $e) {
                debug_add("topic for component '{$component}' not found or accessible");
            }
        }

        return $available_generators;
    }
}
