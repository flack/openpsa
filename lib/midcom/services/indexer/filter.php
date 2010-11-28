<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: filter.php 22991 2009-07-23 16:09:46Z flack $
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
 * @abstract Abstract indexer filter class
 * @package midcom.services
 * @see midcom_services_indexer
 */
class midcom_services_indexer_filter
{
    /**
     * This variable is set by the subclass constructors and indicates
     * the type of the filter.
     *
     * @var string
     */
    var $type = '';

    /**
     * The name of the field that should be restricted.
     *
     * @var string
     * @access protected
     */
    var $_field = '';

    /**
     * Initialize the class.
     *
     * @param string $field The name of the field that should be filtered.
     */
    function __construct($field)
    {
        $this->_field = $field;
    }

    /**
     * Returns the name of the field.
     *
     * @return string
     */
    function get_field()
    {
        return $this->_field;
    }
}
?>