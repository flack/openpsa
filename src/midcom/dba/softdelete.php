<?php
/**
 * @package midcom.dba
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\dba;

use midcom;
use midcom_connection;
use midcom_helper_reflector_tree;
use midgard_query_builder;
use midgard\portable\api\mgdobject;

/**
 * Helpers for recursively undeleting/purging
 *
 * @package midcom.dba
 */
class softdelete
{
    /**
     * Undelete objects
     *
     * @param array $guids
     * @return integer Size of undeleted objects
     * @todo We should only undelete parameters & attachments deleted inside some small window of the main objects delete
     */
    public static function undelete($guids) : int
    {
        $undeleted_size = 0;

        foreach ((array) $guids as $guid) {
            if (!mgdobject::undelete($guid)) {
                debug_add("Failed to undelete object with GUID {$guid} errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                continue;
            }
            // refresh
            $object = midcom::get()->dbfactory->get_object_by_guid($guid);
            $undeleted_size += $object->metadata->size;
            $parent = $object->get_parent();
            if (!empty($parent->guid)) {
                // Invalidate parent from cache so content caches have chance to react
                midcom::get()->cache->invalidate($parent->guid);
            }

            // FIXME: We should only undelete parameters & attachments deleted inside some small window of the main objects delete
            $undeleted_size += self::undelete_parameters($guid);
            $undeleted_size += self::undelete_attachments($guid);

            //FIXME: are we sure we want to undelete all children here unconditionally, shouldn't it be left as UI decision ??
            // List all deleted children
            $children_types = midcom_helper_reflector_tree::get_child_objects($object, true);

            foreach ($children_types as $children) {
                $child_guids = array_column($children, 'guid');
                $undeleted_size += self::undelete($child_guids);
            }
        }

        return $undeleted_size;
    }

    /**
     * Recover the parameters related to a deleted object
     *
     * @return integer Size of undeleted objects
     * @todo We should only undelete parameters & attachments deleted inside some small window of the main objects delete
     */
    public static function undelete_parameters(string $guid) : int
    {
        $undeleted_size = 0;

        $qb = new midgard_query_builder('midgard_parameter');
        $qb->include_deleted();
        $qb->add_constraint('parentguid', '=', $guid);
        $qb->add_constraint('metadata.deleted', '=', true);
        foreach ($qb->execute() as $param) {
            if ($param->undelete($param->guid)) {
                $undeleted_size += $param->metadata->size;
            }
        }

        return $undeleted_size;
    }

    /**
     * Recover the attachments related to a deleted object
     *
     * @return integer Size of undeleted objects
     * @todo We should only undelete parameters & attachments deleted inside some small window of the main objects delete
     */
    public static function undelete_attachments(string $guid) : int
    {
        $undeleted_size = 0;

        $qb = new midgard_query_builder('midgard_attachment');
        $qb->include_deleted();
        $qb->add_constraint('parentguid', '=', $guid);
        $qb->add_constraint('metadata.deleted', '=', true);
        foreach ($qb->execute() as $att) {
            if ($att->undelete($att->guid)) {
                midcom::get()->uimessages->add(midcom::get()->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'), sprintf(midcom::get()->i18n->get_string('attachment %s undeleted', 'midgard.admin.asgard'), $att->name, midcom_connection::get_error_string()));
                $undeleted_size += $att->metadata->size;
                $undeleted_size += self::undelete_parameters($att->guid);
            } else {
                midcom::get()->uimessages->add(midcom::get()->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'), sprintf(midcom::get()->i18n->get_string('failed undeleting attachment %s, reason %s', 'midgard.admin.asgard'), $att->name, midcom_connection::get_error_string()), 'error');
            }
        }

        return $undeleted_size;
    }

    /**
     * Purge objects
     *
     * @return integer Size of purged objects
     */
    public static function purge(array $guids, string $type) : int
    {
        $purged_size = 0;
        $qb = new midgard_query_builder($type);
        $qb->add_constraint('guid', 'IN', $guids);
        $qb->include_deleted();

        foreach ($qb->execute() as $object) {
            // first kill your children
            $children_types = midcom_helper_reflector_tree::get_child_objects($object, true);
            foreach ($children_types as $child_type => $children) {
                $child_guids = array_column($children, 'guid');
                self::purge($child_guids, $child_type);
            }

            // then shoot your dogs
            $purged_size += self::purge_parameters($object->guid);
            $purged_size += self::purge_attachments($object->guid);

            // now shoot yourself
            if (!$object->purge()) {
                debug_add("Failed to purge object " . get_class($object) . " {$object->guid}", MIDCOM_LOG_INFO);
            } else {
                $purged_size += $object->metadata->size;
            }
        }

        return $purged_size;
    }

    /**
     * Purge the parameters related to a deleted object
     *
     * @return integer Size of purged objects
     */
    public static function purge_parameters(string $guid) : int
    {
        $purged_size = 0;

        $qb = new midgard_query_builder('midgard_parameter');
        $qb->include_deleted();
        $qb->add_constraint('parentguid', '=', $guid);
        foreach ($qb->execute() as $param) {
            if ($param->purge()) {
                $purged_size += $param->metadata->size;
            } else {
                midcom::get()->uimessages->add(
                    midcom::get()->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'),
                    sprintf(midcom::get()->i18n->get_string('failed purging parameter %s => %s, reason %s', 'midgard.admin.asgard'), $param->domain, $param->name, midcom_connection::get_error_string()),
                    'error'
                );
            }
        }

        return $purged_size;
    }

    /**
     * Purge the attachments related to a deleted object
     *
     * @return integer Size of purged objects
     */
    public static function purge_attachments(string $guid) : int
    {
        $purged_size = 0;

        $qb = new midgard_query_builder('midgard_attachment');
        $qb->include_deleted();
        $qb->add_constraint('parentguid', '=', $guid);
        foreach ($qb->execute() as $att) {
            if ($att->purge()) {
                $purged_size += $att->metadata->size;
                self::purge_parameters($att->guid);
            } else {
                midcom::get()->uimessages->add(midcom::get()->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'), sprintf(midcom::get()->i18n->get_string('failed purging attachment %s => %s, reason %s', 'midgard.admin.asgard'), $att->guid, $att->name, midcom_connection::get_error_string()), 'error');
            }
        }

        return $purged_size;
    }
}
