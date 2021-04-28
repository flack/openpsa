<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Interface for all indexer query filters.
 *
 * A filter will restrict any query for a given field, showing only results matching
 * the filter. In essence, this is a limited version of the range query facility
 * supported by Lucene.
 *
 * @package midcom.services
 * @see midcom_services_indexer
 */
interface midcom_services_indexer_filter
{
    /**
     * Returns the filter's string representation
     *
     * @return string The string to append to the query
     */
    public function get_query_string() : string;
}
