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
    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();
    }

    public static function get_available_generators() : array
    {
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
                $available_generators[$component] = midcom_db_topic::get_cached($node_guid)->get_label();
            } catch (midcom_error) {
                debug_add("topic for component '{$component}' not found or accessible");
            }
        }

        return $available_generators;
    }
}
