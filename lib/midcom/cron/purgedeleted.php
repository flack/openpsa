<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.services
 */
class midcom_cron_purgedeleted extends midcom_baseclasses_components_cron_handler
{
    private $_cutoff;

    public $quiet = true;

    public function set_cutoff($days)
    {
        $this->_cutoff = mktime(23, 59, 59, date('n'), date('j') - $days, date('Y'));
    }

    public function get_cutoff()
    {
        if (empty($this->_cutoff))
        {
            $this->set_cutoff(midcom::get('config')->get('cron_purge_deleted_after'));
        }
        return $this->_cutoff;
    }

    private function _log($message, $level = MIDCOM_LOG_DEBUG)
    {
        debug_add(trim($message, " \n"), $level);

        if (!$this->quiet)
        {
            if ($level == MIDCOM_LOG_ERROR)
            {
                $message = 'ERROR: ' . $message;
            }
            echo $message . "\n";
            flush();
        }
    }

    public function _on_execute()
    {
        $cut_off = $this->get_cutoff();
        $this->_log('Purging entries deleted before ' . gmdate('Y-m-d H:i:s', $cut_off) . "\n");
        foreach (midcom_connection::get_schema_types() as $mgdschema)
        {
            if (substr($mgdschema, 0, 2) == '__')
            {
                continue;
            }
            if (   class_exists('MidgardReflectorObject')
                && !MidgardReflectorObject::has_metadata_class($mgdschema))
            {
                continue;
            }
            $this->_log("Processing class {$mgdschema}");
            $qb = new midgard_query_builder($mgdschema);
            $qb->add_constraint('metadata.deleted', '<>', 0);
            $qb->add_constraint('metadata.revised', '<', gmdate('Y-m-d H:i:s', $cut_off));
            $qb->include_deleted();
            $qb->set_limit(500);
            $objects = $qb->execute();
            $found = count($objects);
            $purged = 0;
            foreach ($objects as $obj)
            {
                if (!$obj->purge())
                {
                    $this->_log("Failed to purge {$mgdschema} {$obj->guid}, deleted: {$obj->metadata->deleted},  revised: {$obj->metadata->revised}. errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    debug_print_r('Failed object', $obj);
                    continue;
                }
                $purged++;
            }
            if ($found > 0)
            {
                $this->_log("  Found {$found} deleted {$mgdschema} objects, purged {$purged}\n", MIDCOM_LOG_INFO);
            }
            else
            {
                $this->_log("  No {$mgdschema} objects deleted before cutoff date found\n");
            }
        }
    }
}
?>