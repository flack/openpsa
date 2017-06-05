<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for clearing tokens from old send receipts
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_cron_duplicates_clean extends midcom_baseclasses_components_cron_handler
{
    /**
     * Find hanging duplicate marks (that no longer point anywhere) and clear them
     */
    public function _on_execute()
    {
        if (!$this->_config->get('enable_duplicate_search')) {
            debug_add('Duplicate operations disabled, aborting', MIDCOM_LOG_INFO);
            return;
        }

        ignore_user_abort();

        $tried = [];

        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'org.openpsa.contacts.duplicates:possible_duplicate');
        $results = $qb->execute();
        foreach ($results as $param) {
            if (!array_key_exists($param->name, $tried)) {
                try {
                    midcom::get()->dbfactory->get_object_by_guid($param->name);
                    $tried[$param->name] = true;
                } catch (midcom_error $e) {
                    $tried[$param->name] = false;
                }
            }
            if (!$tried[$param->name]) {
                debug_add("GUID {$param->name} points to nonexistent person, removing possible duplicate mark", MIDCOM_LOG_INFO);
                if (!$param->delete()) {
                    debug_add("Failed to delete parameter {$param->guid}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                }
            }
        }
    }
}
