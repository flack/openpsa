<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: purgedeleted.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 *
 *
 * @package midcom.services
 */
class midcom_cron_purgedeleted extends midcom_baseclasses_components_cron_handler
{
    function _on_execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('called!');
        $cut_off = mktime(23, 59, 59, date('n'), date('j')-$GLOBALS['midcom_config']['cron_pure_deleted_after'], date('Y'));
        foreach (midcom_connection::get_schema_types() as $mgdschema)
        {
            if (substr($mgdschema, 0, 2) == '__')
            {
                continue;
            }
            debug_add("Processing class {$mgdschema}");
            $qb = new midgard_query_builder($mgdschema);
            $qb->add_constraint('metadata.deleted', '<>', 0);
            $qb->add_constraint('metadata.revised', '<', gmdate('Y-m-d H:i:s', $cut_off));
            $qb->include_deleted();
            $qb->set_limit(500);
            $objects = $qb->execute();
            unset($qb);
            if (!is_array($objects))
            {
                debug_add("QB failed fatally on class {$mgdschema}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                continue;
            }
            $found = count($objects);
            $purged = 0;
            foreach ($objects as $obj)
            {
                if (!$obj->purge())
                {
                    debug_add("Failed to purge {$mgdschema} {$obj->guid}, deleted: {$obj->metadata->deleted},  revised: {$obj->metadata->revised}. errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    debug_print_r('Failed object', $obj);
                    continue;
                }
                $purged++;
            }
            if ($found > 0)
            {
                debug_add("Found {$found} {$mgdschema} objects deleted before " . date('Y-m-d H:i:s', $cut_off) . ", purged {$purged}", MIDCOM_LOG_INFO);
            }
            else
            {
                debug_add("No {$mgdschema} objects deleted before " . date('Y-m-d H:i:s', $cut_off) . " found");
            }
        }

        debug_pop();
    }
}
?>