<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the interface class for org.openpsa.expenses
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver, org_openpsa_contacts_duplicates_support
{
    /**
     * @inheritdoc
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_projects_hour_report_dba)
        {
            return "hours/edit/{$object->guid}/";
        }
        return null;
    }

    public function get_merge_configuration($object_mode, $merge_mode)
    {
        $config = array();
        if ($merge_mode == 'future')
        {
            /* In theory we could have future things (like resource/manager ships), but now we don't support that mode, we just exit */
            return $config;
        }
        if ($object_mode == 'person')
        {
            $config['org_openpsa_expenses_expense'] = array
            (
                'person' => array
                (
                    'target' => 'id',
                )
            );
        }
        return $config;
    }
}
?>