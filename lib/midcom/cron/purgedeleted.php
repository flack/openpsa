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

    public function set_cutoff($days)
    {
        $this->_cutoff = mktime(23, 59, 59, date('n'), date('j') - $days, date('Y'));
    }

    public function get_cutoff()
    {
        if (empty($this->_cutoff)) {
            $this->set_cutoff(midcom::get()->config->get('cron_purge_deleted_after'));
        }
        return $this->_cutoff;
    }

    public function get_classes()
    {
        $classes = [];
        foreach (midcom_connection::get_schema_types() as $mgdschema) {
            if (   substr($mgdschema, 0, 2) == '__'
                || !midgard_reflector_object::has_metadata_class($mgdschema)) {
                continue;
            }
            $classes[] = $mgdschema;
        }
        return $classes;
    }

    public function execute()
    {
        $cut_off = $this->get_cutoff();
        debug_add('Purging entries deleted before ' . gmdate('Y-m-d H:i:s', $cut_off) . "\n");
        foreach ($this->get_classes() as $mgdschema) {
            debug_add("Processing class {$mgdschema}");
            $stats = $this->process_class($mgdschema);

            foreach ($stats['errors'] as $error) {
                debug_add($error, MIDCOM_LOG_ERROR);
            }
            if ($stats['found'] > 0) {
                debug_add("  Found {$stats['found']} deleted {$mgdschema} objects, purged {$stats['purged']}\n", MIDCOM_LOG_INFO);
            } else {
                debug_add("  No {$mgdschema} objects deleted before cutoff date found\n");
            }
        }
    }

    public function process_class($mgdschema, $limit = 500, $offset = 0)
    {
        $cut_off = $this->get_cutoff();
        $qb = new midgard_query_builder($mgdschema);
        $qb->add_constraint('metadata.deleted', '<>', 0);
        $qb->add_constraint('metadata.revised', '<', gmdate('Y-m-d H:i:s', $cut_off));
        $qb->include_deleted();
        if ($limit) {
            $qb->set_limit($limit);
        }
        if ($offset) {
            $qb->set_offset($offset);
        }
        $objects = $qb->execute();

        $stats = [
            'found' => count($objects),
            'purged' => 0,
            'errors' => []
        ];

        foreach ($objects as $obj) {
            if (!$obj->purge()) {
                $stats['errors'][] = "Failed to purge {$obj->guid}, deleted: {$obj->metadata->deleted},  revised: {$obj->metadata->revised}. errstr: " . midcom_connection::get_error_string();
                debug_print_r('Purge failed for object', $obj);
                continue;
            }
            $stats['purged']++;
        }
        return $stats;
    }
}
