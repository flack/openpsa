<?php
/**
 * @package midcom.services
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 *
 * @package midcom.services
 */
interface midcom_services_permalinks_resolver
{
    /**
     * Build a link for the given object, relative to the topic
     *
     * @param midcom_db_topic $topic The topic to probe
     * @param midcom_core_dbaobject $object The object to resolve
     * @return string|null A string (even an empty one) is considered success, null a failure
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object);
}