<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class provides an abstract base class for all indexer query filters.
 *
 * A filter will restrict any query for a given field, showing only results matching
 * the filter. In essence, this is a limited version of the range query facility
 * supported by Lucene.
 *
 * @package midcom.services
 * @see midcom_services_indexer
 */
abstract class midcom_services_indexer_filter
{
    /**
     * The name of the field that should be restricted.
     *
     * @var string
     */
    protected $_field = '';

    /**
     * Initialize the class.
     *
     * @param string $field The name of the field that should be filtered.
     */
    public function __construct($field)
    {
        $this->_field = $field;
    }

    /**
     * Returns the name of the field.
     *
     * @return string
     */
    public function get_field() : string
    {
        return $this->_field;
    }

    /**
     * Returns the filter's string representation
     *
     * @return string The string to append to the query
     */
    abstract public function get_query_string();
}
