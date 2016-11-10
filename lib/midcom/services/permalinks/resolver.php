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
     * This interface function is used to check whether a component can handle a given object.
     *
     * A topic is provided can limit the "scope" of the search accordingly. It can be
     * safely assumed that the topic given is a valid topic in the MidCOM content tree
     * (it is checked through NAP).
     *
     * If the GUID could be successfully resolved, a URL local to the given topic without a
     * leading slash must be returned (f.x. 'article/'), empty strings ('') are allowed
     * indicating root page access. If the GUID is invalid, null will be returned.
     *
     * The information you return with this call (if no-null) will be considered cacheable by
     * the content caching engine. Therefore you have to ensure that either the resolution
     * is stable or that you configure the content cache accordingly if you have a match.
     * The hard way is setting the no_cache flag in cases where you need full flexibility, but
     * this should be avoided for the sake of performance if somehow possible. The more
     * sophisticated alternative is therefore to selectively invalidate all GUIDs that have
     * their Permalink lookup affected.
     *
     * @param midcom_db_topic $topic The topic to probe
     * @param midcom_core_dbaobject $object The object to resolve
     * @return string|null A string (even an empty one) is considered success, null a failure
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object);
}
